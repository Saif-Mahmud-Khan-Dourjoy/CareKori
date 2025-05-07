<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OtpCode;
use Illuminate\Support\Carbon;

class OtpController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|regex:/^01[3-9][0-9]{8}$/']);
        $code = rand(1000, 9999);

        OtpCode::updateOrCreate(
            ['phone' => $request->phone],
            ['code' => $code, 'expires_at' => now()->addMinutes(5), 'is_verified' => false]
        );

        return response()->json(['message' => 'OTP sent', 'otp' => $code]); // Simulated
    }

    // public function sendOtp(Request $request)
    // {
    //     $request->validate([
    //         'phone' => 'required|regex:/^01[3-9][0-9]{8}$/',
    //     ]);

    //     $otpCode = rand(1000, 9999);

    //     // OTP কোড ডাটাবেজে সংরক্ষণ
    //     OtpCode::updateOrCreate(
    //         ['phone' => $request->phone],
    //         [
    //             'code' => $otpCode,
    //             'expires_at' => Carbon::now()->addMinutes(5),
    //             'is_verified' => false,
    //         ]
    //     );

    //     $token = env('BD_BULK_SMS_API_TOKEN');
    //     $to = $request->phone;
    //     $message = "Your OTP code is: $otpCode";

    //     $url = "https://api.bdbulksms.net/api.php?json";
    //     $data = [
    //         'to' => $to,
    //         'message' => $message,
    //         'token' => $token,
    //     ];

    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    //     curl_setopt($ch, CURLOPT_ENCODING, '');
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     $smsResult = curl_exec($ch);
    //     $curlError = curl_error($ch);
    //     curl_close($ch);

    //     if ($curlError) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'cURL Error: ' . $curlError,
    //         ], 500);
    //     }

    //     $response = json_decode($smsResult, true);

    //     if (json_last_error() !== JSON_ERROR_NONE) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Invalid JSON response',
    //             'raw_response' => $smsResult,
    //         ], 500);
    //     }

    //     $statusMessages = [];
    //     foreach ($response as $res) {
    //         $status = $res['status'] ?? 'UNKNOWN';
    //         $statusMsg = $res['statusmsg'] ?? 'No status message';
    //         $statusMessages[] = [
    //             'to' => $res['to'] ?? 'Unknown',
    //             'status' => $status,
    //             'message' => $statusMsg,
    //         ];
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'messages' => $statusMessages,
    //     ]);
    // }

    public function verifyOtp(Request $request)
    {
        $request->validate(['phone' => 'required', 'code' => 'required']);

        $otp = OtpCode::where('phone', $request->phone)
            ->where('code', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) return response()->json(['message' => 'Invalid or expired OTP'], 422);

        $otp->update(['is_verified' => true]);
        return response()->json(['message' => 'OTP verified']);
    }
}