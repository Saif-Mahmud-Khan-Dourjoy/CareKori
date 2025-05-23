<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function initiate(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1',
            ]);

            $tran_id = uniqid('txn_');

            $post_data = [
                'store_id' => config('services.sslcommerz.store_id'),
                'store_passwd' => config('services.sslcommerz.store_password'),
                'total_amount' => $request->amount,
                'currency' => 'BDT',
                'tran_id' => $tran_id,
                'success_url' => url('/api/payment/success'),
                'fail_url' => url('/api/payment/fail'),
                'cancel_url' => url('/api/payment/fail'),
                'ipn_url' => url('/api/payment/ipn'),
                'cus_name' => 'Test User',
                'cus_email' => 'test@example.com',
                'cus_add1' => 'Dhaka',
                'cus_phone' => '01700000000',
                'product_name' => 'Recharge',
                'product_category' => 'Digital',
                'product_profile' => 'general',
            ];

            $url = config('services.sslcommerz.sandbox_mode')
                ? 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php'
                : 'https://securepay.sslcommerz.com/gwprocess/v3/api.php';



            $response = Http::asForm()->post($url, $post_data)->json();


            if (!empty($response['GatewayPageURL'])) {
                return response()->json(['payment_url' => $response['GatewayPageURL']]);
            }

            return response()->json(['message' => 'Failed to create payment.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function success(Request $request)
    {
        $valId = $request->val_id;

        if (!$valId) return response()->json(['message' => 'Invalid response'], 400);

        $validation = Http::get('https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php', [
            'val_id' => $valId,
            'store_id' => config('services.sslcommerz.store_id'),
            'store_passwd' => config('services.sslcommerz.store_password'),
            'format' => 'json'
        ])->json();

        $tran_id = $validation['tran_id'] ?? null;

        if ($validation['status'] === 'VALID') {
            // return response()->json(['message' => 'Payment verified successfully', 'transaction_id' => $validation['tran_id']]);
            return redirect()->away("http://localhost:3000/payment-success?tran_id=$tran_id");
        }

        return response()->json(['message' => 'Payment verification failed'], 400);
    }

    public function fail(Request $request)
    {
        return redirect()->away("http://localhost:3000/payment-fail");
    }

    public function ipn(Request $request)
    {
        // same as success() — optionally validate IPN
        return response()->json(['message' => 'IPN received']);
    }
}