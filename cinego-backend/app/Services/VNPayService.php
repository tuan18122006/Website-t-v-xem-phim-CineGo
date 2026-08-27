<?php

namespace App\Services;

class VNPayService
{
    public function createPaymentUrl(array $data): string
    {
        $vnp_TmnCode = config('vnpay.tmn_code');
        $vnp_HashSecret = config('vnpay.hash_secret');
        $vnp_Url = config('vnpay.url');
        $vnp_ReturnUrl = config('vnpay.return_url');

        $vnp_TxnRef = $data['txn_ref'];
        $vnp_OrderInfo = $data['order_info'];
        $vnp_Amount = $data['amount'] * 100;
        $vnp_IpAddr = $data['ip_address'] ?? request()->ip();
        $vnp_ExpireDate = $data['expire_date'] ?? now()->addMinutes(9)->format('YmdHis');


        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => now()->format('YmdHis'),
            "vnp_ExpireDate" => $vnp_ExpireDate,
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => $vnp_ReturnUrl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        ksort($inputData);

        $query = "";
        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $query = rtrim($query, '&');
        $vnpSecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
        $vnp_Url .= "?" . $query . '&vnp_SecureHash=' . $vnpSecureHash;

        return $vnp_Url;
    }

    public function verifyReturnUrl(array $vnpParams): bool
    {
        $vnp_HashSecret = config('vnpay.hash_secret');

        $vnp_SecureHash = $vnpParams['vnp_SecureHash'] ?? '';
        unset($vnpParams['vnp_SecureHash']);
        unset($vnpParams['vnp_SecureHashType']);

        ksort($vnpParams);

        $hashData = "";
        $i = 0;
        foreach ($vnpParams as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        return $secureHash === $vnp_SecureHash;
    }

    public function refund($booking, $amount, $isPartial = false)
    {
        $vnp_TmnCode = config('vnpay.tmn_code');
        $vnp_HashSecret = config('vnpay.hash_secret');
        // Sandbox API Refund URL
        $vnp_ApiUrl = "https://sandbox.vnpayment.vn/merchant_webapi/api/transaction";

        $vnp_RequestId = "RF" . time() . rand(100, 999);
        $vnp_Command = "refund";
        $vnp_TransactionType = $isPartial ? "03" : "02"; // 02: Full, 03: Partial
        $vnp_TxnRef = $booking->vnp_txn_ref;
        $vnp_Amount = $amount * 100;
        $vnp_OrderInfo = "Hoan tien giao dich " . $vnp_TxnRef;
        $vnp_TransactionNo = "0"; 
        $vnp_TransactionDate = $booking->vnp_transaction_date;
        $vnp_CreateBy = "Admin";
        $vnp_CreateDate = now()->format('YmdHis');
        $vnp_IpAddr = request()->ip() ?? "127.0.0.1";

        if (!$vnp_TransactionDate) {
            throw new \Exception("Không tìm thấy thời gian giao dịch gốc của VNPay.");
        }

        $datamac = $vnp_RequestId . "|" . "2.1.0" . "|" . $vnp_Command . "|" . $vnp_TmnCode . "|" . $vnp_TransactionType . "|" . $vnp_TxnRef . "|" . $vnp_Amount . "|" . $vnp_TransactionNo . "|" . $vnp_TransactionDate . "|" . $vnp_CreateBy . "|" . $vnp_CreateDate . "|" . $vnp_IpAddr . "|" . $vnp_OrderInfo;
        
        $vnp_SecureHash = hash_hmac('sha512', $datamac, $vnp_HashSecret);

        $data = [
            "vnp_RequestId" => $vnp_RequestId,
            "vnp_Version" => "2.1.0",
            "vnp_Command" => $vnp_Command,
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_TransactionType" => $vnp_TransactionType,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_Amount" => $vnp_Amount,
            "vnp_TransactionNo" => $vnp_TransactionNo,
            "vnp_TransactionDate" => $vnp_TransactionDate,
            "vnp_CreateBy" => $vnp_CreateBy,
            "vnp_CreateDate" => $vnp_CreateDate,
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_SecureHash" => $vnp_SecureHash
        ];

        $ch = curl_init($vnp_ApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        
        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        if (isset($response['vnp_ResponseCode']) && $response['vnp_ResponseCode'] === '00') {
            return true;
        }

        throw new \Exception("Lỗi VNPay Refund: " . ($response['vnp_Message'] ?? 'Unknown error'));
    }
}