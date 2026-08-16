<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actor;
use App\Models\Combo;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    public function index(): JsonResponse
    {
        $movies = Movie::onlyTrashed()
            ->with('genres:id,name')
            ->get(['id', 'title', 'poster_url', 'deleted_at']);

        $showtimes = Showtime::onlyTrashed()
            ->with('movie:id,title', 'room:id,name')
            ->get(['id', 'movie_id', 'room_id', 'start_time', 'deleted_at']);

        $actors = Actor::onlyTrashed()
            ->get(['id', 'name', 'avatar_url', 'deleted_at']);

        $genres = Genre::onlyTrashed()
            ->get(['id', 'name', 'deleted_at']);

        $combos = Combo::onlyTrashed()
            ->get(['id', 'name', 'image_url', 'deleted_at']);

        $vouchers = Voucher::onlyTrashed()
            ->get(['id', 'code', 'deleted_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'movies' => $movies,
                'showtimes' => $showtimes,
                'actors' => $actors,
                'genres' => $genres,
                'combos' => $combos,
                'vouchers' => $vouchers,
            ],
        ], 200);
    }

    public function restore(string $type, int $id): JsonResponse
    {
        $item = $this->resolveItem($type, $id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy dữ liệu trong thùng rác.'], 404);
        }

        $item->restore();

        return response()->json([
            'success' => true,
            'message' => $this->label($type) . ' đã được khôi phục thành công.',
        ], 200);
    }

    public function forceDelete(string $type, int $id): JsonResponse
    {
        $item = $this->resolveItem($type, $id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy dữ liệu trong thùng rác.'], 404);
        }

        if ($type === 'movie' && $item->showtimes()->withTrashed()->whereHas('bookings')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa vĩnh viễn phim này vì còn vé đã bán liên quan.',
            ], 400);
        }

        if ($type === 'showtime' && $item->bookings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa vĩnh viễn suất chiếu này vì còn vé đã bán liên quan.',
            ], 400);
        }

        if ($type === 'voucher' && $item->bookings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa vĩnh viễn voucher này vì đã được dùng trong đơn hàng.',
            ], 400);
        }

        if ($type === 'combo' && $item->bookingCombos()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa vĩnh viễn combo này vì đã được dùng trong đơn hàng.',
            ], 400);
        }

        $item->forceDelete();

        return response()->json([
            'success' => true,
            'message' => $this->label($type) . ' đã được xóa vĩnh viễn khỏi hệ thống.',
        ], 200);
    }

    public function emptyAll(): JsonResponse
    {
        foreach (['movies' => Movie::class, 'actors' => Actor::class, 'genres' => Genre::class] as $group => $model) {
            $model::onlyTrashed()->forceDelete();
        }

        $locked = Showtime::onlyTrashed()->whereHas('bookings')->count();

        Showtime::onlyTrashed()
            ->whereDoesntHave('bookings')
            ->forceDelete();

        Voucher::onlyTrashed()->whereDoesntHave('bookings')->forceDelete();

        Combo::onlyTrashed()->whereDoesntHave('bookingCombos')->forceDelete();

        return response()->json([
            'success' => true,
            'message' => $locked > 0
                ? 'Đã dọn thùng rác. Còn ' . $locked . ' suất chiếu giữ lại vì có vé đã bán.'
                : 'Đã dọn toàn bộ thùng rác.',
        ], 200);
    }

    private function resolveItem(string $type, int $id)
    {
        return match ($type) {
            'movie' => Movie::onlyTrashed()->find($id),
            'showtime' => Showtime::onlyTrashed()->find($id),
            'actor' => Actor::onlyTrashed()->find($id),
            'genre' => Genre::onlyTrashed()->find($id),
            'combo' => Combo::onlyTrashed()->find($id),
            'voucher' => Voucher::onlyTrashed()->find($id),
            default => null,
        };
    }

    private function label(string $type): string
    {
        return match ($type) {
            'movie' => 'Phim',
            'showtime' => 'Suất chiếu',
            'actor' => 'Diễn viên',
            'genre' => 'Thể loại',
            'combo' => 'Combo',
            'voucher' => 'Voucher',
            default => 'Dữ liệu',
        };
    }
}