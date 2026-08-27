<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalCompletedNotification;
use App\Notifications\WithdrawalRejectedNotification;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    private const MIN_WITHDRAW = 50000;

    private const BANKS = [
        'Vietcombank', 'VietinBank', 'BIDV', 'Agribank', 'Techcombank',
        'MB Bank', 'VPBank', 'ACB', 'Sacombank', 'TPBank', 'OCB',
        'VIB', 'SHB', 'MSB', 'HDBank', 'Eximbank', 'Momo', 'ZaloPay',
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        $transactions = $user->walletTransactions()
            ->orderByDesc('id')
            ->paginate(20);

        $withdrawals = $user->withdrawals()
            ->orderByDesc('id')
            ->take(10)
            ->get();

        return response()->json([
            'balance' => (float) $user->wallet_balance,
            'transactions' => $transactions,
            'withdrawals' => $withdrawals,
        ]);
    }

    public function banks()
    {
        return response()->json(['banks' => self::BANKS]);
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:' . self::MIN_WITHDRAW,
            'qr_image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'bank_name' => 'nullable|string|max:50',
            'bank_account' => 'nullable|string|max:30',
            'bank_holder' => 'nullable|string|max:100',
        ], [
            'amount.required' => 'Vui lòng nhập số tiền cần rút.',
            'amount.min' => 'Số tiền rút tối thiểu là ' . number_format(self::MIN_WITHDRAW, 0, ',', '.') . 'đ.',
            'qr_image.image' => 'File tải lên phải là hình ảnh QR.',
        ]);

        if (!$request->hasFile('qr_image') && (!$request->bank_name || !$request->bank_account || !$request->bank_holder)) {
            return response()->json([
                'message' => 'Vui lòng cung cấp hình ảnh QR hoặc điền đầy đủ thông tin ngân hàng.',
            ], 422);
        }

        $user = $request->user();
        $amount = (float) $request->amount;

        DB::beginTransaction();
        try {
            $user = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();

            $hasPending = \App\Models\Withdrawal::where('user_id', $user->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPending) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đang có yêu cầu rút tiền đang chờ duyệt. Vui lòng chờ xử lý xong.'
                ], 422);
            }

            if ($user->wallet_balance < $amount) {
                throw new \Exception('Số dư ví không đủ để rút.');
            }

            $qrPath = null;
            if ($request->hasFile('qr_image')) {
                $qrPath = $request->file('qr_image')->store('withdrawals', 'public');
            }

            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'bank_name' => $request->bank_name ?? 'QR Code',
                'bank_account' => $request->bank_account ?? 'QR Code',
                'bank_holder' => $request->bank_holder ?? 'QR Code',
                'qr_image' => $qrPath,
                'status' => 'pending',
            ]);

            $description = $qrPath ? "Rút tiền bằng mã QR" : "Rút tiền về {$request->bank_name} tài khoản {$request->bank_account}";

            app(WalletService::class)->debit(
                $user,
                $amount,
                $description,
                'withdraw',
                $withdrawal
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã gửi yêu cầu rút tiền. Chờ admin duyệt.',
                'withdrawal' => $withdrawal,
                'new_balance' => (float) $user->wallet_balance,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

   
    public function adminList(Request $request)
    {
        $query = Withdrawal::with('user:id,name,email,phone')
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'completed', 'rejected')")
            ->orderByDesc('id');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->paginate(20)]);
    }

   
    public function adminComplete(Request $request, $id)
    {
        $withdrawal = Withdrawal::with('user')->findOrFail($id);

        if (!in_array($withdrawal->status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Yêu cầu này không còn ở trạng thái chờ duyệt.'
            ], 422);
        }

        $withdrawal->status = 'completed';
        $withdrawal->processed_by = $request->user()->id;
        $withdrawal->processed_at = now();
        $withdrawal->save();

        // Gửi thông báo cho khách
        $withdrawal->user->notify(new WithdrawalCompletedNotification(
            (float) $withdrawal->amount,
            $withdrawal->id
        ));

        return response()->json(['success' => true, 'message' => 'Đã xác nhận hoàn tất chuyển khoản.']);
    }

   
    public function adminReject(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:255',
        ]);

        $withdrawal = Withdrawal::with('user')->findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Yêu cầu này không còn ở trạng thái chờ duyệt.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $withdrawal->status = 'rejected';
            $withdrawal->admin_note = $request->admin_note;
            $withdrawal->processed_by = $request->user()->id;
            $withdrawal->processed_at = now();
            $withdrawal->save();

            app(WalletService::class)->credit(
                $withdrawal->user,
                (float) $withdrawal->amount,
                "Hoàn lại tiền rút bị từ chối. Yêu cầu #{$withdrawal->id}",
                'refund',
                $withdrawal
            );

            DB::commit();

            // Gửi thông báo cho khách
            $withdrawal->user->notify(new WithdrawalRejectedNotification(
                (float) $withdrawal->amount,
                $withdrawal->id,
                $request->admin_note
            ));

            return response()->json(['success' => true, 'message' => 'Đã từ chối và hoàn tiền lại ví cho khách.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}