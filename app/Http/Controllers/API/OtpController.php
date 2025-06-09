<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Carbon;

class OtpController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|regex:/^01[3-9][0-9]{8}$/']);
        $code = 1234; // Fixed OTP code

        // Set timezone to Dhaka
        $now = Carbon::now('Asia/Dhaka');
        $expiresAt = $now->copy()->addMinutes(5);

        OtpCode::updateOrCreate(
            ['phone' => $request->phone],
            ['code' => $code, 'expires_at' => $expiresAt, 'is_verified' => false]
        );

        return response()->json(['message' => 'OTP sent', 'otp' => $code,'expires_at' => $expiresAt]); // Simulated
    }

    // public function sendOtp(Request $request)
    // {
    //     $request->validate([
    //         'phone' => 'required|regex:/^01[3-9][0-9]{8}$/',
    //     ]);

    //     $otpCode = 1234; // Fixed OTP code


    //     OtpCode::updateOrCreate(
    //         ['phone' => $request->phone],
    //         [
    //             'code' => $otpCode,
    //             'expires_at' => Carbon::now('Asia/Dhaka')->addMinutes(5),
    //             'is_verified' => false,
    //         ]
    //     );

    //     $statusMessages= $this->sendToPhone($request, $otpCode);
    //     if (!$statusMessages) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to send OTP',
    //         ], 500);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'messages' => $statusMessages,
    //     ]);
    // }

    public function verifyOtp(Request $request)
    {
        $request->validate(['phone' => 'required', 'code' => 'required']);

        $now = Carbon::now('Asia/Dhaka');

        $otp = OtpCode::where('phone', $request->phone)
            ->where('code', $request->code)
            ->where('expires_at', '>', $now)
            ->first();

        if (!$otp) return response()->json(['message' => 'Invalid or expired OTP'], 422);

        $otp->update(['is_verified' => true]);
        return response()->json(['message' => 'OTP verified']);
    }



    public function sendToPhone(Request $request, $otpCode)
    {
        $token = env('BD_BULK_SMS_API_TOKEN');
        $to = $request->phone;
        $message = "Your OTP code is: $otpCode";

        $url = "https://api.bdbulksms.net/api.php?json";
        $data = [
            'to' => $to,
            'message' => $message,
            'token' => $token,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $smsResult = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return response()->json([
                'success' => false,
                'message' => 'cURL Error: ' . $curlError,
            ], 500);
        }

        $response = json_decode($smsResult, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON response',
                'raw_response' => $smsResult,
            ], 500);
        }

        $statusMessages = [];
        foreach ($response as $res) {
            $status = $res['status'] ?? 'UNKNOWN';
            $statusMsg = $res['statusmsg'] ?? 'No status message';
            $statusMessages[] = [
                'to' => $res['to'] ?? 'Unknown',
                'status' => $status,
                'message' => $statusMsg,
            ];
        }

        return $statusMessages;
    }



    public function resendOtp(Request $request)
    {
        // Validate the incoming request (phone number required)
        $validated = $request->validate([
            'phone' => 'required|regex:/^01[3-9][0-9]{8}$/',  // Ensure the phone number is valid
        ]);

        $phone = $validated['phone'];

        // Check if there is an existing OTP record for this phone
        $otpRecord = OtpCode::where('phone', $phone)
            ->where('is_verified', false) // Ensure OTP hasn't been verified
            ->first();

        // If an expired OTP exists, delete it and generate a new one
        if ($otpRecord) {
            // Check if the OTP is expired
            if ($otpRecord->expires_at < Carbon::now('Asia/Dhaka')) {
                // Delete the expired OTP record
                $otpRecord->delete();
            } else {
                // If OTP is still valid, resend it
                $statusMessages = $this->sendToPhone($request, $otpRecord->code);
                if (!$statusMessages) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to resend OTP',
                    ], 500);
                }

                return response()->json([
                    'success' => true,
                    'messages' => $statusMessages,
                ]);
            }
        }

        // If no valid OTP or expired OTP exists, generate a new OTP
        $otp = 1234; // Fixed OTP code

        // Store the new OTP code
        OtpCode::updateOrCreate(
            ['phone' => $phone],
            [
                'code' => $otp,
                'expires_at' => Carbon::now('Asia/Dhaka')->addMinutes(5), // Set expiration time
                'is_verified' => false, // Mark as unverified
            ]
        );

        // Send the new OTP
        $statusMessages = $this->sendToPhone($request, $otp);

        if (!$statusMessages) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend OTP',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'messages' => $statusMessages,
        ]);
    }






    public function sendOtpForPhoneChange(Request $request)
    {

        // Validate phone number
        $validated = $request->validate([
            'new_phone' => 'required|regex:/^01[3-9][0-9]{8}$/|unique:users,phone',  // Validate new phone number
        ]);

        $phone = $validated['new_phone'];

        // Generate OTP
        $otp = 1234; // Fixed OTP code

        // Store OTP in database
        OtpCode::updateOrCreate(
            ['phone' => $phone], // Lookup criteria
            [
                'code' => $otp,
                'is_verified' => false,
                'expires_at' => Carbon::now('Asia/Dhaka')->addMinutes(5),
            ]
        );

        // Send OTP via SMS or Email (For simplicity, sending via email)
        // $statusMessages= $this->sendToPhone($request, $otp);

        // if (!$statusMessages) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Failed to resend OTP',
        //     ], 500);
        // }

        // return response()->json([
        //     'success' => true,
        //     'messages' => $statusMessages,
        // ]);

        return response()->json([
            'message' => 'OTP sent successfully',
            'otp' => $otp, // For testing purposes, you can return the OTP
        ]);
    }

    // Verify OTP and change phone number
    public function verifyOtpAndChangePhone(Request $request)
    {
        $validated = $request->validate([
            'new_phone' => 'required|regex:/^01[3-9][0-9]{8}$/',  // Ensure valid phone number format
            'otp' => 'required|digits:4',  // OTP should be 4 digits
        ]);

        $phone = $validated['new_phone'];
        $otp = $validated['otp'];

        // Check if the OTP exists and is unverified
        $otpRecord = OtpCode::where('phone', $phone)
            ->where('code', $otp)
            ->where('is_verified', false)
            ->where('expires_at', '>', Carbon::now('Asia/Dhaka'))
            ->first();

        if (!$otpRecord) {
            return response()->json(['error' => 'Invalid or expired OTP'], 400);
        }

        // Verify the OTP
        $otpRecord->is_verified = true;
        $otpRecord->save();

        // Update the user's phone number

        $user = User::find(auth()->user()->id); // Fetch the user model
        $user->phone = $phone; // Update the phone number
        $user->save();



        return response()->json(['message' => 'Phone number updated successfully']);
    }


    public function sendOtpForForgetPassword(Request $request)
    {
        $request->validate(['phone' => 'required|regex:/^01[3-9][0-9]{8}$/']);
        $code = 1234; // Fixed OTP code

        // Check if the user exists with the provided phone number
        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }



        $otpRecord = OtpCode::where('phone', $request->phone)
            ->where('is_verified', false)
            ->first();
        if ($otpRecord && $otpRecord->expires_at > Carbon::now('Asia/Dhaka')) {
            return response()->json(['message' => 'OTP already sent and valid'], 200);
        }

        OtpCode::updateOrCreate(
            ['phone' => $request->phone],
            ['code' => $code, 'expires_at' => Carbon::now('Asia/Dhaka')->addMinutes(5), 'is_verified' => false]
        );

        // $statusMessages = $this->sendToPhone($request, $code);
        // if (!$statusMessages) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Failed to resend OTP',
        //     ], 500);
        // }

        // return response()->json([
        //     'success' => true,
        //     'messages' => $statusMessages,
        // ]);

        return response()->json(['message' => 'OTP sent for password reset', 'otp' => $code]); // Simulated
    }
   public function verifyOtpForForgetPassword(Request $request)
{
    $request->validate(['phone' => 'required', 'code' => 'required']);
    
    $otp = OtpCode::where('phone', $request->phone)
        ->where('code', $request->code)
        ->where('expires_at', '>', Carbon::now('Asia/Dhaka')) // Compare in UTC
        ->first();
    
    if (!$otp) {
        return response()->json(['message' => 'Invalid or expired OTP'], 422);
    }
    
    $otp->update(['is_verified' => true]);
    return response()->json(['message' => 'OTP verified for password reset']);
}
    public function updatePasswordAfterForget(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^01[3-9][0-9]{8}$/',
            'code' => 'required|min:4',
            'new_password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/|confirmed',
        ]);

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Check if the OTP is verified
        $otp = OtpCode::where('phone', $request->phone)
            ->where('is_verified', true)
            ->where('code', $request->code)
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'OTP not verified'], 422);
        }

        // Update the user's password
        $user->password = bcrypt($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    }
}
