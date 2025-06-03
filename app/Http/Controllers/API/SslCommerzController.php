<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SslCommerzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SslCommerzController extends Controller
{
    protected SslCommerzService $sslCommerz;

    public function __construct(SslCommerzService $sslCommerz)
    {
        $this->sslCommerz = $sslCommerz;
    }

    // 1. Initiate Payment
    // public function initiatePayment(Request $request)
    // {
    //     $request->validate([
    //         'total_amount' => 'required|numeric|min:1',
    //         'currency' => 'required|string|in:BDT,USD',
    //         'cus_name' => 'required|string',
    //         'cus_email' => 'required|email',
    //         'cus_add1' => 'required|string',
    //         'cus_phone' => 'required|string',
    //     ]);

    //     $tranId = 'tran_' . Str::random(10);

    //     $payload = [
    //         'total_amount' => $request->total_amount,
    //         'currency' => $request->currency,
    //         'tran_id' => $tranId,
    //         'success_url' => route('api.sslcommerz.success'),
    //         'fail_url' => route('api.sslcommerz.fail'),
    //         'cancel_url' => route('api.sslcommerz.cancel'),
    //         'ipn_url' => 'https://e0bf-103-84-39-241.ngrok-free.app/api/sslcommerz/ipn',


    //         'cus_name' => $request->cus_name,
    //         'cus_email' => $request->cus_email,
    //         'cus_add1' => $request->cus_add1,
    //         'cus_phone' => $request->cus_phone,

    //         'shipping_method' => 'NO',
    //         'product_name' => $request->product_name ?? 'Sample Product',
    //         'product_category' => $request->product_category ?? 'General',
    //         'product_profile' => 'general',
    //     ];

    //     $response = $this->sslCommerz->initiatePayment($payload);

    //     if (isset($response['GatewayPageURL'])) {
    //         return response()->json([
    //             'payment_url' => $response['GatewayPageURL'],
    //             'tran_id' => $tranId,
    //         ]);
    //     }

    //     return response()->json([
    //         'message' => 'Failed to initiate payment',
    //         'error' => $response,
    //     ], 500);
    // }

    // 2. Payment Success Callback
    // public function success(Request $request)
    // {
    //     // You will get transaction info here. Validate and update your DB
    //     return response()->json([
    //         'message' => 'Payment Success',
    //         'data' => $request->all(),
    //     ]);
    // }

    // 3. Payment Fail Callback
    public function fail(Request $request)
    {
        $tranId = $request->tran_id ?? null;

        if ($tranId) {
            $order = Order::where('transaction_id', $tranId)->first();
            if ($order) {
                $order->update(['status' => 'failed']);
            }
        }

        return response()->json([
            'message' => 'Payment Failed',
            'data' => $request->all(),
        ]);
    }

    public function cancel(Request $request)
    {
        $tranId = $request->tran_id ?? null;

        if ($tranId) {
            $order = Order::where('transaction_id', $tranId)->first();
            if ($order) {
                $order->update(['status' => 'canceled']);
            }
        }

        return response()->json([
            'message' => 'Payment Cancelled',
            'data' => $request->all(),
        ]);
    }

    // 5. IPN Handler (Instant Payment Notification)
    // public function ipn(Request $request)
    // {
    //     $ipnData = $request->all();

    //     \Log::info('SSLCommerz IPN Received:', $ipnData);

    //     // Basic validation example - check required fields
    //     if (!isset($ipnData['tran_id']) || !isset($ipnData['status'])) {
    //         \Log::warning('Invalid IPN data received');
    //         return response('Invalid IPN', 400);
    //     }

    //     // Validate transaction status (success indicators may vary)
    //     $validStatuses = ['VALID', 'VALIDATED', 'SUCCESS'];
    //     if (in_array(strtoupper($ipnData['status']), $validStatuses)) {
    //         // TODO: Verify more details if needed, e.g., verify signature/hash if provided
    //         // TODO: Check that tran_id exists in your orders/payments table
    //         // TODO: Verify the amount matches the expected amount

    //         // Example: Update order/payment status in your DB
    //         // $order = Order::where('transaction_id', $ipnData['tran_id'])->first();
    //         // if ($order) {
    //         //     $order->status = 'paid';
    //         //     $order->payment_verified_at = now();
    //         //     $order->save();
    //         // }

    //         \Log::info("Payment verified for tran_id: {$ipnData['tran_id']}");
    //     } else {
    //         // Payment failed or suspicious
    //         \Log::warning("Payment failed or invalid for tran_id: {$ipnData['tran_id']} with status {$ipnData['status']}");
    //     }

    //     // Return 200 OK to acknowledge receipt
    //     return response('IPN Received', 200);
    // }


    // public function ipn(Request $request)
    // {
    //     $ipnData = $request->all();

    //     \Log::info('IPN Received:', $ipnData);

    //     if (!isset($ipnData['val_id'])) {
    //         \Log::warning('IPN missing val_id');
    //         return response('Invalid IPN', 400);
    //     }

    //     // Call validation API with val_id from IPN data
    //     $validation = Http::get('https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php', [
    //         'val_id' => $ipnData['val_id'],
    //         'store_id' => config('services.sslcommerz.store_id'),
    //         'store_passwd' => config('services.sslcommerz.store_password'),
    //         'format' => 'json',
    //     ]);

    //     if ($validation->failed()) {
    //         \Log::error('Validation API call failed');
    //         return response('Validation failed', 500);
    //     }

    //     $validationData = $validation->json();

    //     if (in_array(strtoupper($validationData['status']), ['VALID', 'VALIDATED', 'SUCCESS'])) {
    //         // Update order/payment status in your database here
    //         \Log::info("Payment verified via IPN for tran_id: {$validationData['tran_id']}");
    //     } else {
    //         \Log::warning("Payment invalid via IPN for tran_id: {$validationData['tran_id']}");
    //     }

    //     return response('IPN Processed', 200);
    // }



    // 6. Refund API
    // public function refund(Request $request)
    // {
    //     $request->validate([
    //         'bank_tran_id' => 'required|string',
    //         'refund_trans_id' => 'required|string',
    //         'refund_amount' => 'required|numeric|min:1',
    //         'refund_remarks' => 'required|string',
    //         'refe_id' => 'nullable|string',
    //     ]);

    //     $params = [
    //         'bank_tran_id' => $request->bank_tran_id,
    //         'refund_trans_id' => $request->refund_trans_id,
    //         'refund_amount' => $request->refund_amount,
    //         'refund_remarks' => $request->refund_remarks,
    //     ];

    //     if ($request->has('refe_id')) {
    //         $params['refe_id'] = $request->refe_id;
    //     }

    //     $response = $this->sslCommerz->refundTransaction($params);

    //     if (isset($response['status']) && strtolower($response['status']) === 'success') {
    //         return response()->json([
    //             'message' => 'Refund successful',
    //             'data' => $response,
    //         ]);
    //     }

    //     return response()->json([
    //         'message' => 'Refund failed',
    //         'data' => $response,
    //     ], 400);
    // }

    public function initiatePayment(Request $request)
    {
        $request->validate([
            'total_amount' => 'required|numeric|min:1',
            'currency' => 'required|string|in:BDT,USD',
            'cus_name' => 'required|string',
            'cus_email' => 'required|email',
            'cus_add1' => 'required|string',
            'cus_phone' => 'required|string',
        ]);

        $tranId = 'tran_' . Str::random(10);

        // Create order in DB with pending status
        $order = Order::create([
            'transaction_id' => $tranId,
            'amount' => $request->total_amount,
            'currency' => $request->currency,
            'status' => 'pending',
            'customer_name' => $request->cus_name,
            'customer_email' => $request->cus_email,
            'customer_phone' => $request->cus_phone,
            'customer_address' => $request->cus_add1,
        ]);

        $payload = [
            'total_amount' => $request->total_amount,
            'currency' => $request->currency,
            'tran_id' => $tranId,
            'success_url' => route('api.sslcommerz.success'),
            'fail_url' => route('api.sslcommerz.fail'),
            'cancel_url' => route('api.sslcommerz.cancel'),
            'ipn_url' => route('api.sslcommerz.ipn'),

            'cus_name' => $request->cus_name,
            'cus_email' => $request->cus_email,
            'cus_add1' => $request->cus_add1,
            'cus_phone' => $request->cus_phone,

            'shipping_method' => 'NO',
            'product_name' => $request->product_name ?? 'Sample Product',
            'product_category' => $request->product_category ?? 'General',
            'product_profile' => 'general',
        ];

        $response = $this->sslCommerz->initiatePayment($payload);

        if (isset($response['GatewayPageURL'])) {
            return response()->json([
                'payment_url' => $response['GatewayPageURL'],
                'tran_id' => $tranId,
            ]);
        }

        // If initiation failed, mark order failed
        $order->update(['status' => 'failed']);

        return response()->json([
            'message' => 'Failed to initiate payment',
            'error' => $response,
        ], 500);
    }

    public function success(Request $request)
    {
        $valId = $request->val_id ?? null;
        if (!$valId) {
            return response()->json(['message' => 'Validation ID (val_id) missing'], 400);
        }

        $validationData = $this->validatePayment($valId);
        if (!$validationData) {
            return response()->json(['message' => 'Payment validation failed'], 400);
        }

        $order = Order::where('transaction_id', $validationData['tran_id'])->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if (in_array(strtoupper($validationData['status']), ['VALID', 'VALIDATED', 'SUCCESS'])) {
            $order->update([
                'status' => 'paid',
                'bank_tran_id' => $validationData['bank_tran_id'] ?? null,
                'payment_verified_at' => now(),
            ]);

            return response()->json([
                'message' => 'Payment verified successfully',
                'data' => $validationData,
            ]);
        }

        $order->update(['status' => 'failed']);

        return response()->json([
            'message' => 'Payment validation failed',
            'data' => $validationData,
        ], 400);
    }

    public function ipn(Request $request)
    {
        $ipnData = $request->all();
        \Log::info('SSLCommerz IPN received:', $ipnData);

        if (!isset($ipnData['val_id'])) {
            \Log::warning('IPN missing val_id');
            return response('Invalid IPN', 400);
        }

        $validationData = $this->validatePayment($ipnData['val_id']);
        if (!$validationData) {
            \Log::warning('IPN payment validation failed');
            return response('Validation failed', 400);
        }

        $order = Order::where('transaction_id', $validationData['tran_id'])->first();
        if (!$order) {
            \Log::warning('Order not found for IPN tran_id: ' . $validationData['tran_id']);
            return response('Order not found', 404);
        }

        if (in_array(strtoupper($validationData['status']), ['VALID', 'VALIDATED', 'SUCCESS'])) {
            $order->update([
                'status' => 'paid',
                'bank_tran_id' => $validationData['bank_tran_id'] ?? null,
                'payment_verified_at' => now(),
            ]);
            \Log::info("Payment verified via IPN for tran_id: {$validationData['tran_id']}");
        } else {
            $order->update(['status' => 'failed']);
            \Log::warning("Payment failed or invalid for tran_id: {$validationData['tran_id']}");
        }

        return response('IPN processed', 200);
    }

    public function refund(Request $request)
    {
        $request->validate([
            'bank_tran_id' => 'required|string',
            'refund_trans_id' => 'required|string',
            'refund_amount' => 'required|numeric|min:1',
            'refund_remarks' => 'required|string',
            'refe_id' => 'nullable|string',
        ]);

        $order = Order::where('bank_tran_id', $request->bank_tran_id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found for given bank_tran_id'], 404);
        }

        $params = [
            'bank_tran_id' => $request->bank_tran_id,
            'refund_trans_id' => $request->refund_trans_id,
            'refund_amount' => $request->refund_amount,
            'refund_remarks' => $request->refund_remarks,
        ];

        if ($request->has('refe_id')) {
            $params['refe_id'] = $request->refe_id;
        }

        $response = $this->sslCommerz->refundTransaction($params);

        if (isset($response['status']) && strtolower($response['status']) === 'success') {
            $order->update([
                'status' => 'refunded',
                'refund_tran_id' => $request->refund_trans_id,
            ]);

            return response()->json([
                'message' => 'Refund successful',
                'data' => $response,
            ]);
        }

        return response()->json([
            'message' => 'Refund failed',
            'data' => $response,
        ], 400);
    }

    // Payment validation helper method
    private function validatePayment(string $valId): ?array
    {
        $response = Http::get('https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php', [
            'val_id' => $valId,
            'store_id' => config('services.sslcommerz.store_id'),
            'store_passwd' => config('services.sslcommerz.store_password'),
            'format' => 'json',
        ]);

        if ($response->failed()) {
            \Log::error('Validation API call failed.');
            return null;
        }

        $data = $response->json();

        $validStatuses = ['VALID', 'VALIDATED', 'SUCCESS'];
        if (!isset($data['status']) || !in_array(strtoupper($data['status']), $validStatuses)) {
            \Log::warning('Payment validation failed:', $data);
            return null;
        }

        return $data;
    }
}