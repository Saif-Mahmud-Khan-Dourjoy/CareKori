<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\CommonNotification;
use App\Services\SslCommerzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SslCommerzController extends Controller
{
    protected SslCommerzService $sslCommerz;



    public function __construct(SslCommerzService $sslCommerz)
    {
        $this->sslCommerz = $sslCommerz;
    }

    public function initiatePayment(Request $request)
    {


        $request->validate([
            'total_amount' => 'required|numeric|min:1',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $user->load('customerProfile');

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
            'ipn_url' => route('api.sslcommerz.ipn'),
            // 'ipn_url' => 'https://4c746502864e.ngrok-free.app/api/sslcommerz/ipn',


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

        if (isset($response['GatewayPageURL']) && !empty($response['GatewayPageURL'])) {
            return response()->json([
                'message' => 'Payment session created successfully',
                'payment_url' => $response['GatewayPageURL'],
                'tran_id' => $tranId,
            ]);
        }

        $order->update(['status' => 'failed']);

        return response()->json([
            'message' => 'Failed to initiate payment',
            'error' => $response,
        ], 500);
    }

    public function success(Request $request)
    {
        Log::info('SSLCommerz success callback received', $request->all());

        $valId = $request->input('val_id');

        if (!$valId) {
            return response()->json([
                'message' => 'Validation ID missing',
            ], 400);
        }

        $validationData = $this->sslCommerz->validatePayment($valId);

        if (!$validationData) {
            return response()->json([
                'message' => 'Payment validation failed',
            ], 400);
        }

        return $this->processSuccessfulPayment($validationData, 'success_url');
    }


    public function ipn(Request $request)
    {
        Log::info('SSLCommerz IPN received', $request->all());

        $valId = $request->input('val_id');

        if (!$valId) {
            return response('Invalid IPN: val_id missing', 400);
        }

        $validationData = $this->sslCommerz->validatePayment($valId);

        if (!$validationData) {
            return response('Payment validation failed', 400);
        }

        $this->processSuccessfulPayment($validationData, 'ipn');

        return response('IPN processed', 200);
    }




    public function fail(Request $request)
    {
        Log::warning('SSLCommerz fail callback received', $request->all());

        $tranId = $request->input('tran_id');

        if ($tranId) {
            $order = Order::where('transaction_id', $tranId)->first();

            if ($order && $order->status !== 'paid') {
                $order->update(['status' => 'failed']);
            }
        }

        return response()->json([
            'message' => 'Payment failed',
            'data' => $request->all(),
        ]);
    }

    public function cancel(Request $request)
    {
        Log::warning('SSLCommerz cancel callback received', $request->all());

        $tranId = $request->input('tran_id');

        if ($tranId) {
            $order = Order::where('transaction_id', $tranId)->first();

            if ($order && $order->status !== 'paid') {
                $order->update(['status' => 'canceled']);
            }
        }

        return response()->json([
            'message' => 'Payment cancelled',
            'data' => $request->all(),
        ]);
    }


    private function processSuccessfulPayment(array $validationData, string $source)
    {
        $tranId = $validationData['tran_id'] ?? null;

        if (!$tranId) {
            return response()->json([
                'message' => 'Transaction ID missing from validation response',
            ], 400);
        }

        return DB::transaction(function () use ($validationData, $tranId, $source) {
            $order = Order::where('transaction_id', $tranId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found',
                ], 404);
            }

            if ($order->status === 'paid') {
                return response()->json([
                    'message' => 'Payment already processed',
                    'data' => $validationData,
                ]);
            }

            $paidAmount = (float) ($validationData['amount'] ?? 0);
            $expectedAmount = (float) $order->amount;

            if ($paidAmount > 0 && $paidAmount !== $expectedAmount) {
                Log::warning('SSLCommerz amount mismatch', [
                    'tran_id' => $tranId,
                    'expected_amount' => $expectedAmount,
                    'paid_amount' => $paidAmount,
                ]);

                $order->update(['status' => 'amount_mismatch']);

                return response()->json([
                    'message' => 'Payment amount mismatch',
                    'data' => $validationData,
                ], 400);
            }

            if (($validationData['risk_level'] ?? 0) == 1) {
                $order->update([
                    'status' => 'on_hold',
                    'bank_tran_id' => $validationData['bank_tran_id'] ?? null,
                ]);

                return response()->json([
                    'message' => 'Payment is valid but risky. Manual verification required.',
                    'data' => $validationData,
                ]);
            }

            $order->update([
                'status' => 'paid',
                'bank_tran_id' => $validationData['bank_tran_id'] ?? null,
                'payment_verified_at' => now('Asia/Dhaka'),
            ]);

            if ($order->user && $order->user->wallet) {
                $order->user->wallet->increment('balance', $order->amount);
            }

            if ($order->user) {
                $order->user->notify(
                    new CommonNotification(
                        $order->user,
                        'Payment Successful',
                        "Your payment amount {$order->amount} has been successfully processed."
                    )
                );
            }

            Log::info('SSLCommerz payment processed successfully', [
                'tran_id' => $tranId,
                'source' => $source,
            ]);

            return response()->json([
                'message' => 'Payment verified successfully',
                'data' => $validationData,
            ]);
        });
    }




    public function refund(Request $request)
    {
        try {
            $request->validate([
                'bank_tran_id' => 'required|string',
                'refund_amount' => 'required|numeric|min:1',
                'refund_remarks' => 'required|string',
                'refe_id' => 'nullable|string',
            ]);

            $order = Order::where('bank_tran_id', $request->bank_tran_id)->first();

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found for given bank_tran_id',
                ], 404);
            }

            if ((float) $order->amount < (float) $request->refund_amount) {
                return response()->json([
                    'message' => 'Refund amount cannot be more than paid amount',
                ], 400);
            }

            $refundTransId = 'refund_' . Str::random(10);

            $params = [
                'bank_tran_id' => $request->bank_tran_id,
                'refund_trans_id' => $refundTransId,
                'refund_amount' => $request->refund_amount,
                'refund_remarks' => $request->refund_remarks,
            ];

            if ($request->filled('refe_id')) {
                $params['refe_id'] = $request->refe_id;
            }

            $response = $this->sslCommerz->refundTransaction($params);

            if (isset($response['status']) && strtolower($response['status']) === 'success') {
                $order->update([
                    'refund_tran_id' => $refundTransId,
                ]);

                $order->refund()->updateOrCreate(
                    [],
                    [
                        'status' => $response['status'],
                        'refund_amount' => $request->refund_amount,
                        'refund_ref_id' => $response['refund_ref_id'] ?? null,
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
        } catch (\Throwable $e) {
            Log::error('Refund API exception', [
                'message' => $e->getMessage(),
            ]);

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
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }

        $response = $this->sslCommerz->refundStatus([
            'refund_ref_id' => $request->refund_ref_id,
        ]);

        $status = strtolower(trim($response['status'] ?? ''));

        if ($status === 'refunded') {
            return response()->json([
                'message' => 'Refunded successfully',
                'data' => $response,
            ]);
        }

        if ($status === 'processing') {
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

   
}