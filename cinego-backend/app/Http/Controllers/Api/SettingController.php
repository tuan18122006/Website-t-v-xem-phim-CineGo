<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    private $settingsFile = 'payment_settings.json';

    public function getPaymentSettings()
    {
        $defaultSettings = [
            'vietqr_bank_id' => '',
            'vietqr_account_no' => '',
            'vietqr_account_name' => '',
        ];

        if (Storage::exists($this->settingsFile)) {
            $content = Storage::get($this->settingsFile);
            $settings = json_decode($content, true);
            return response()->json([
                'success' => true,
                'data' => array_merge($defaultSettings, $settings ?? [])
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $defaultSettings
        ]);
    }

    public function updatePaymentSettings(Request $request)
    {
        $request->validate([
            'vietqr_bank_id' => 'required|string',
            'vietqr_account_no' => 'required|string',
            'vietqr_account_name' => 'required|string',
        ]);

        $settings = [
            'vietqr_bank_id' => $request->vietqr_bank_id,
            'vietqr_account_no' => $request->vietqr_account_no,
            'vietqr_account_name' => strtoupper(trim($request->vietqr_account_name)),
        ];

        Storage::put($this->settingsFile, json_encode($settings, JSON_PRETTY_PRINT));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật cấu hình thanh toán thành công'
        ]);
    }
}
