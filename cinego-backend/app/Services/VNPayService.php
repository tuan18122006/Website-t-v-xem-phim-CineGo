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

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => now()->format('YmdHis'),
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
}