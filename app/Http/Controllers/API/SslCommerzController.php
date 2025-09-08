<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\CommonNotification;
use App\Services\SslCommerzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SslCommerzController extends Controller
{
    protected SslCommerzService $sslCommerz;



    public function __construct(SslCommerzService $sslCommerz)
    {
        $this->sslCommerz = $sslCommerz;
    }




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

        ]);


        $user = Auth::user()->load('customerProfile');





        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }



        $tranId = 'tran_' . Str::random(10);

        // Create order in DB with pending status
        $order = Order::create([
            'user_id' => $user->id,
            'transaction_id' => $tranId,
            'amount' => $request->total_amount,
            'customer_name' => $user->name ?? "N/A",
            'customer_email' => $user->email ?? "N/A",
            'customer_phone' => $user->phone ?? "N/A",
            'customer_address' => $user->address ?? "N/A",
        ]);

        $payload = [
            'total_amount' => $request->total_amount,
            'currency' => "BDT",
            'tran_id' => $tranId,

            'success_url' => route('api.sslcommerz.success'),
            'fail_url' => route('api.sslcommerz.fail'),
            'cancel_url' => route('api.sslcommerz.cancel'),
            // 'ipn_url' => route('api.sslcommerz.ipn'),
            'ipn_url' => 'https://4c746502864e.ngrok-free.app/api/sslcommerz/ipn',


            'emi_option' => 0,

            'cus_name' => $user->name ?? "N/A",
            'cus_email' => $user->email ?? "N/A",
            'cus_add1' => $user->address ?? "N/A",
            'cus_phone' => $user->phone ?? "N/A",
            'cus_city' => $user->customerProfile->district ?? "N/A",
            'cus_postcode' => data_get($user, 'customerProfile.postcode', 'N/A'),
            'cus_country' => data_get($user, 'customerProfile.country', 'N/A'),


            'shipping_method' => 'NO',
            'product_name' => 'Digital Product',
            'product_category' => "Digital",
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
                'payment_verified_at' => now('Asia/Dhaka'),
            ]);

            if ($order->user && $order->user->wallet) {
                $order->user->wallet->increment('balance', $order->amount);
            }

            $order->user->notify(new CommonNotification($order->user,  "Payment Successful", "Your payment amount {$order->amount} has been successfully processed."));

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

    // public function ipn(Request $request)
    // {
    //     $ipnData = $request->all();
    //     \Log::info('SSLCommerz IPN received:', $ipnData);

    //     exit();

    //     if (!isset($ipnData['val_id'])) {
    //         \Log::warning('IPN missing val_id');
    //         return response('Invalid IPN', 400);
    //     }

    //     $validationData = $this->validatePayment($ipnData['val_id']);
    //     if (!$validationData) {
    //         \Log::warning('IPN payment validation failed');
    //         return response('Validation failed', 400);
    //     }

    //     $order = Order::where('transaction_id', $validationData['tran_id'])->first();
    //     if (!$order) {
    //         \Log::warning('Order not found for IPN tran_id: ' . $validationData['tran_id']);
    //         return response('Order not found', 404);
    //     }

    //     if (in_array(strtoupper($validationData['status']), ['VALID', 'VALIDATED', 'SUCCESS'])) {
    //         $order->update([
    //             'status' => 'paid',
    //             'bank_tran_id' => $validationData['bank_tran_id'] ?? null,
    //             'payment_verified_at' => now(),
    //         ]);
    //     } else {
    //         $order->update(['status' => 'failed']);
    //         \Log::warning("Payment failed or invalid for tran_id: {$validationData['tran_id']}");
    //     }

    //     return response('IPN processed', 200);
    // }

    public function refund(Request $request)
    {

        try {
            $request->validate([
                'bank_tran_id' => 'required|string',
                // 'refund_trans_id' => 'required|string',
                'refund_amount' => 'required|numeric|min:1',
                'refund_remarks' => 'required|string',
                'refe_id' => 'nullable|string',
            ]);

            $order = Order::where('bank_tran_id', $request->bank_tran_id)->first();



            if (!$order) {
                return response()->json(['message' => 'Order not found for given bank_tran_id'], 404);
            }

            if ($order->amount < $request->refund_amount) {
                return response()->json(['message' => 'Refund amount can not be more than paid amount'], 400);
            }

            $refundTransId = 'refund_' . Str::random(10);

            $params = [
                'bank_tran_id' => $request->bank_tran_id,
                'refund_trans_id' => $refundTransId,
                'refund_amount' => $request->refund_amount,
                'refund_remarks' => $request->refund_remarks,
            ];

            if ($request->has('refe_id')) {
                $params['refe_id'] = $request->refe_id;
            }

            $response = $this->sslCommerz->refundTransaction($params);



            if (isset($response['status']) && strtolower($response['status']) === 'success') {
                $order->update([
                    // 'status' => 'refunded',
                    'refund_tran_id' => $request->refund_trans_id,
                ]);



                $order->refund()->updateOrCreate(
                    [],
                    [
                        'status' => $response['status'],
                        'refund_amount' => $request->refund_amount,
                        'refund_ref_id' => $response['refund_ref_id'],
                        'refund_remark' => $request->refund_remarks,
                    ]
                );

                if ($order->user && $order->user->wallet) {
                    $order->user->wallet->decrement('balance', $request->refund_amount);
                }

                return response()->json([
                    'message' => 'Refund successful',
                    'data' => $response,
                ]);
            }

            return response()->json([
                'message' => 'Refund failed',
                'data' => $response,
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Regund API call exception: ' . $e->getMessage());
            return response()->json([
                'message' => 'Refund failed',

            ], 400);
        }
    }



    public function refundStatus(Request $request)
    {
        $request->validate([
            'refund_ref_id' => 'required|string',
        ]);

        $order = Order::whereHas('refund', function ($query) use ($request) {
            $query->where('refund_ref_id', $request->refund_ref_id);
        })->first();



        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }




        $params = [
            'refund_ref_id' => $request->refund_ref_id,
        ];



        $response = $this->sslCommerz->refundStatus($params);

   

       



        if (isset($response['status']) && strtolower(trim($response['status'])) === 'refunded') { 


            return response()->json([
                'message' => 'Refunded successful',
                'data' => $response,
            ]);
        }



        if (isset($response['status']) && strtolower(trim($response['status'])) === 'processing') {


            return response()->json([
                'message' => 'Refund is processing',
                'data' => $response,
            ]);
        }

        return response()->json([
            'message' => 'Refund failed',
            'data' => $response,
        ], 400);
    }

    // Payment validation helper method
    // private function validatePayment(string $valId): ?array
    // {
    //     $response = Http::get('https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php', [
    //         'val_id' => $valId,
    //         'store_id' => config('services.sslcommerz.store_id'),
    //         'store_passwd' => config('services.sslcommerz.store_password'),
    //         'format' => 'json',
    //     ]);

    //     if ($response->failed()) {
    //         \Log::error('Validation API call failed.');
    //         return null;
    //     }

    //     $data = $response->json();

    //     $validStatuses = ['VALID', 'VALIDATED', 'SUCCESS'];
    //     if (!isset($data['status']) || !in_array(strtoupper($data['status']), $validStatuses)) {
    //         \Log::warning('Payment validation failed:', $data);
    //         return null;
    //     }

    //     return $data;
    // }

    private function validatePayment(string $valId): ?array
    {
        try {

            $sandbox = config('services.sslcommerz.sandbox', true);

            $url = $sandbox
                ? 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php'
                : 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php';


            $response = Http::get($url, [
                'val_id' => $valId,
                'store_id' => config('services.sslcommerz.store_id'),
                'store_passwd' => config('services.sslcommerz.store_password'),
                'format' => 'json',
            ]);

            // Check for failed request
            if ($response->failed()) {
                \Log::error('Validation API call failed.', ['val_id' => $valId]);
                return null;
            }

            // Decode the JSON response
            $data = $response->json();

            // Define valid statuses
            $validStatuses = ['VALID', 'VALIDATED', 'SUCCESS'];
            $pendingStatuses = ['PENDING'];
            $failedStatuses = ['FAILED', 'CANCELLED', 'EXPIRED', 'ERROR'];

            // Check if the payment status is one of the valid statuses
            if (isset($data['status']) && in_array(strtoupper($data['status']), $validStatuses)) {
                return $data;  // Successful payment validation
            }

            // Handle the different statuses
            if (isset($data['status']) && in_array(strtoupper($data['status']), $pendingStatuses)) {
                \Log::info('Payment is pending:', ['response' => $data]);
                return null;  // Handle pending payments as needed
            }

            if (isset($data['status']) && in_array(strtoupper($data['status']), $failedStatuses)) {
                \Log::warning('Payment failed or cancelled:', ['response' => $data]);
                return null;  // Handle failed or cancelled payments
            }

            // If the status is unknown or not listed, return null
            \Log::warning('Unknown payment status:', ['response' => $data]);
            return null;
        } catch (\Exception $e) {
            \Log::error('Validation API call exception: ' . $e->getMessage());
            return null;
        }
    }


    // Test
    // public function ipn(Request $request)
    // {
    //     $ipnData = $request->all();
    //     \Log::info('SSLCommerz IPN received:', $ipnData);

    //     // Validate IPN data
    //     if (!isset($ipnData['val_id'])) {
    //         \Log::warning('IPN missing val_id');
    //         return response('Invalid IPN', 400);
    //     }

    //     // Validate the payment (for both payment and refund)
    //     $validationData = $this->validatePayment($ipnData['val_id']);
    //     if (!$validationData) {
    //         \Log::warning('IPN payment validation failed');
    //         return response('Validation failed', 400);
    //     }

    //     // Find the order based on the transaction ID
    //     $order = Order::where('transaction_id', $validationData['tran_id'])->first();
    //     if (!$order) {
    //         \Log::warning('Order not found for IPN tran_id: ' . $validationData['tran_id']);
    //         return response('Order not found', 404);
    //     }

    //     // Check if it's a refund and process accordingly
    //     if (isset($ipnData['refund_ref_id'])) {
    //         // Handle refund logic
    //         $refundRefId = $ipnData['refund_ref_id'];
    //         $refundAmount = $ipnData['refund_amount'] ?? 0;

    //         // If refund is successful, update the order to 'refunded'
    //         if (in_array(strtoupper($validationData['status']), ['VALID', 'SUCCESS'])) {
    //             $order->update([
    //                 'status' => 'refunded', // Set order status to 'refunded'
    //                 'refund_ref_id' => $refundRefId, // Store the refund ref ID
    //                 'refund_amount' => $refundAmount, // Store the refund amount
    //                 'payment_verified_at' => now(),
    //             ]);

    //             \Log::info("Refund processed for tran_id: {$validationData['tran_id']}, refund_ref_id: {$refundRefId}");
    //         } else {
    //             // Handle refund failure or cancellation
    //             \Log::warning("Refund failed or invalid for tran_id: {$validationData['tran_id']}, refund_ref_id: {$refundRefId}");
    //         }
    //     } else {
    //         // Handle regular payment (if no refund transaction ID is present)
    //         if (in_array(strtoupper($validationData['status']), ['VALID', 'VALIDATED', 'SUCCESS'])) {
    //             $order->update([
    //                 'status' => 'paid', // Mark the order as paid
    //                 'bank_tran_id' => $validationData['bank_tran_id'] ?? null, // Store bank transaction ID
    //                 'payment_verified_at' => now(), // Set payment verified timestamp
    //             ]);

    //             \Log::info("Payment verified for tran_id: {$validationData['tran_id']}");
    //         } else {
    //             $order->update(['status' => 'failed']); // Mark the order as failed
    //             \Log::warning("Payment failed or invalid for tran_id: {$validationData['tran_id']}");
    //         }
    //     }

    //     return response('IPN processed', 200);
    // }
}