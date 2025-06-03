<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SslCommerzService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SslCommerzController extends Controller
{
    protected SslCommerzService $sslCommerz;

    public function __construct(SslCommerzService $sslCommerz)
    {
        $this->sslCommerz = $sslCommerz;
    }

    // 1. Initiate Payment
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

        $payload = [
            'total_amount' => $request->total_amount,
            'currency' => $request->currency,
            'tran_id' => $tranId,
            'success_url' => route('api.sslcommerz.success'),
            'fail_url' => route('api.sslcommerz.fail'),
            'cancel_url' => route('api.sslcommerz.cancel'),
            'ipn_url' => 'https://e0bf-103-84-39-241.ngrok-free.app/api/sslcommerz/ipn',
            

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

        return response()->json([
            'message' => 'Failed to initiate payment',
            'error' => $response,
        ], 500);
    }

    // 2. Payment Success Callback
    public function success(Request $request)
    {
        // You will get transaction info here. Validate and update your DB
        return response()->json([
            'message' => 'Payment Success',
            'data' => $request->all(),
        ]);
    }

    // 3. Payment Fail Callback
    public function fail(Request $request)
    {
        return response()->json([
            'message' => 'Payment Failed',
            'data' => $request->all(),
        ]);
    }

    // 4. Payment Cancel Callback
    public function cancel(Request $request)
    {
        return response()->json([
            'message' => 'Payment Cancelled',
            'data' => $request->all(),
        ]);
    }

    // 5. IPN Handler (Instant Payment Notification)
    public function ipn(Request $request)
    {
        $ipnData = $request->all();

        \Log::info('SSLCommerz IPN Received:', $ipnData);

        // Basic validation example - check required fields
        if (!isset($ipnData['tran_id']) || !isset($ipnData['status'])) {
            \Log::warning('Invalid IPN data received');
            return response('Invalid IPN', 400);
        }

        // Validate transaction status (success indicators may vary)
        $validStatuses = ['VALID', 'VALIDATED', 'SUCCESS'];
        if (in_array(strtoupper($ipnData['status']), $validStatuses)) {
            // TODO: Verify more details if needed, e.g., verify signature/hash if provided
            // TODO: Check that tran_id exists in your orders/payments table
            // TODO: Verify the amount matches the expected amount

            // Example: Update order/payment status in your DB
            // $order = Order::where('transaction_id', $ipnData['tran_id'])->first();
            // if ($order) {
            //     $order->status = 'paid';
            //     $order->payment_verified_at = now();
            //     $order->save();
            // }

            \Log::info("Payment verified for tran_id: {$ipnData['tran_id']}");
        } else {
            // Payment failed or suspicious
            \Log::warning("Payment failed or invalid for tran_id: {$ipnData['tran_id']} with status {$ipnData['status']}");
        }

        // Return 200 OK to acknowledge receipt
        return response('IPN Received', 200);
    }


    // 6. Refund API
    public function refund(Request $request)
    {
        $request->validate([
            'bank_tran_id' => 'required|string',
            'refund_trans_id' => 'required|string',
            'refund_amount' => 'required|numeric|min:1',
            'refund_remarks' => 'required|string',
            'refe_id' => 'nullable|string',
        ]);

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
}