<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRecordRequest;
use App\Http\Requests\UpdatePaymentRecordRequest;
use App\Models\PaymentRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentRecordController extends Controller
{
    public function store(StorePaymentRecordRequest $request): JsonResponse
    {
        $data = $request->validated();

        
        if ($data['sent_via'] === 'MFS') {
            $data['sent_bank_acc'] = null;
        } else { // BANK
            $data['sent_trx_id'] = null;
        }

       
        $data['trx_datetime'] = Carbon::parse($data['trx_datetime']);
        $data['amount_received_datetime'] = Carbon::parse($data['amount_received_datetime']);
        $data['sent_datetime'] = Carbon::parse($data['sent_datetime']);

        return DB::transaction(function () use ($data) {
            $userId = (int) $data['user_id'];

            
            $totalIncome = (float) DB::table('appointments')
                ->where('provider_id', $userId)
                ->where('status', 'completed')
                ->sum('price');

           
            $amountReceivedBefore = (float) PaymentRecord::where('user_id', $userId)
                ->sum('sent_amount');

         
            $amountDueBefore = $totalIncome - $amountReceivedBefore;

            
            $sentAmount = (float) $data['sent_amount'];
            if ($sentAmount > $amountDueBefore) {
                return response()->json([
                    'message' => 'Sent amount exceeds current due.',
                    'errors' => [
                        'sent_amount' => ["Max allowed is " . number_format($amountDueBefore, 2, '.', '') . " for this provider (current due)."],
                    ],
                ], 422);
            }

            
            $data['amount_data'] = [
                'history' => [
                    'total_income' => round($totalIncome, 2),
                    'amount_received' => round($amountReceivedBefore, 2),
                    'amount_due' => round($amountDueBefore, 2),
                    'snapshot_at' => now()->toDateTimeString(),
                ],
            ];

            
            $record = PaymentRecord::create($data);

            return response()->json([
                'message' => 'Payment record created successfully.',
                'data' => $record,
            ], 201);
        });
    }


    public function lastByUser(User $user): JsonResponse
    {
        $last = PaymentRecord::where('user_id', $user->id)
            ->orderByDesc('id') 
            ->first();

        if (!$last) {
            return response()->json([
                'message' => 'No payment record found for this user.',
                'data' => null,
            ], 200);
        }

        return response()->json([
            'message' => 'Last payment record fetched.',
            'data' => [
                'id' => $last->id,
                'user_id' => $last->user_id,

               
                'total_income' => $last->total_income,
                'amount_received' => $last->amount_received,
                'amount_due' => $last->amount_due,

               
                'trx_datetime' => $last->trx_datetime,
                'trx_id' => $last->trx_id,
                'sent_datetime' => $last->sent_datetime,
                'sent_via' => $last->sent_via,
            ],
        ], 200);
    }

    // public function providersPaymentSummary(Request $request)
    // {
    //     $userIdFilter   = $request->query('user_id'); // optional for modal
    //     $onlyWithIncome = (int) $request->query('only_with_income', 0);

    //     // 1) total_income from completed appointments
    //     $incomeSub = DB::table('appointments')
    //         ->selectRaw('provider_id as user_id, COALESCE(SUM(price),0) as total_income')
    //         ->where('status', 'completed')
    //         ->groupBy('provider_id');

    //     // 2) total_received from payment_records sum(sent_amount)
    //     $receivedSub = DB::table('payment_records')
    //         ->selectRaw('user_id, COALESCE(SUM(sent_amount),0) as total_received')
    //         ->groupBy('user_id');

    //     /**
    //      * 3) Last payment record (FULL row fields you need for details)
    //      * Pick the latest row by MAX(id) per user.
    //      */
    //     $lastPaymentSub = DB::table('payment_records as pr')
    //         ->select([
    //             'pr.user_id',
    //             'pr.id as last_payment_id',

    //             // trx fields
    //             'pr.trx_datetime as last_trx_datetime',
    //             'pr.trx_id as last_trx_id',
    //             'pr.amount_received_datetime as last_amount_received_datetime',

    //             // admin payout fields
    //             'pr.sent_amount as last_sent_amount',
    //             'pr.sent_datetime as last_sent_datetime',
    //             'pr.sent_via as last_sent_via',
    //             'pr.sent_trx_id as last_sent_trx_id',
    //             'pr.sent_bank_acc as last_sent_bank_acc',

    //             // json + timestamps
    //             'pr.amount_data as last_amount_data',
    //             'pr.created_at as last_created_at',
    //             'pr.updated_at as last_updated_at',
    //         ])
    //         ->whereIn('pr.id', function ($q) {
    //             $q->selectRaw('MAX(id)')
    //                 ->from('payment_records')
    //                 ->groupBy('user_id');
    //         });

    //     // 4) Providers base query + joins
    //     $usersQuery = User::query()
    //         ->select('users.*')
    //         ->with([
    //             'role',
    //             'availability',
    //             'doctorProfile' => fn($q) => $q->select('*')->with(['doctorType', 'doctorSpeciality', 'doctorTitle']),
    //             'lawyerProfile' => fn($q) => $q->select('*')->with(['lawyerTitle', 'lawyerSpeciality']),
    //             'commonProfile' => fn($q) => $q->select('*')->with(['commonSpeciality', 'uniqueIdentification']),
    //         ])
    //         ->whereHas('role', function ($q) {
    //             $q->whereNotIn(DB::raw('LOWER(name)'), ['customer', 'moderator', 'super admin', 'super_admin']);
    //         })
    //         ->leftJoinSub($incomeSub, 'income', fn($join) => $join->on('income.user_id', '=', 'users.id'))
    //         ->leftJoinSub($receivedSub, 'received', fn($join) => $join->on('received.user_id', '=', 'users.id'))
    //         ->leftJoinSub($lastPaymentSub, 'lastpay', fn($join) => $join->on('lastpay.user_id', '=', 'users.id'))
    //         ->addSelect([
    //             DB::raw('COALESCE(income.total_income, 0) as total_income'),
    //             DB::raw('COALESCE(received.total_received, 0) as total_received'),
    //             DB::raw('(COALESCE(income.total_income, 0) - COALESCE(received.total_received, 0)) as amount_due'),

    //             // last payment record columns (selected as last_* fields)
    //             DB::raw('lastpay.last_payment_id as last_payment_id'),
    //             DB::raw('lastpay.last_trx_datetime as last_trx_datetime'),
    //             DB::raw('lastpay.last_trx_id as last_trx_id'),
    //             DB::raw('lastpay.last_amount_received_datetime as last_amount_received_datetime'),

    //             DB::raw('lastpay.last_sent_amount as last_sent_amount'),
    //             DB::raw('lastpay.last_sent_datetime as last_sent_datetime'),
    //             DB::raw('lastpay.last_sent_via as last_sent_via'),
    //             DB::raw('lastpay.last_sent_trx_id as last_sent_trx_id'),
    //             DB::raw('lastpay.last_sent_bank_acc as last_sent_bank_acc'),

    //             DB::raw('lastpay.last_amount_data as last_amount_data'),
    //             DB::raw('lastpay.last_created_at as last_created_at'),
    //             DB::raw('lastpay.last_updated_at as last_updated_at'),
    //         ]);

    //     // optional: single provider (modal)
    //     if ($userIdFilter) {
    //         $usersQuery->where('users.id', (int) $userIdFilter);
    //     }

    //     // optional: list view filter (income > 0)
    //     if ($onlyWithIncome === 1) {
    //         $usersQuery->whereRaw('COALESCE(income.total_income, 0) > 0');
    //     }

    //     $users = $usersQuery->get();

    //     $providers = $users->map(function ($u) {
    //         // profile normalization (same as your previous logic)
    //         if ($u->doctorProfile) {
    //             $profileType = $u->role?->name;
    //             $profile = $u->doctorProfile;
    //         } elseif ($u->lawyerProfile) {
    //             $profileType = $u->role?->name;
    //             $profile = $u->lawyerProfile;
    //         } elseif ($u->commonProfile) {
    //             $profileType = $u->role?->name;
    //             $profile = $u->commonProfile;
    //         } else {
    //             $profileType = 'none';
    //             $profile = null;
    //         }

    //         $specializedAt = match (strtolower((string) $profileType)) {
    //             'doctor' => $profile?->doctorSpeciality?->specialized_at,
    //             'lawyer' => $profile?->lawyerSpeciality?->specialized_at,
    //             'none' => null,
    //             default => $profile?->commonSpeciality?->specialized_at,
    //         };

    //         // Build last payment record object (or null if none)
    //         $lastPaymentRecord = $u->last_payment_id ? [
    //             'id' => (int) $u->last_payment_id,

    //             'trx_datetime' => $u->last_trx_datetime,
    //             'trx_id' => $u->last_trx_id,
    //             'amount_received_datetime' => $u->last_amount_received_datetime,

    //             'sent_amount' => $u->last_sent_amount,
    //             'sent_datetime' => $u->last_sent_datetime,
    //             'sent_via' => $u->last_sent_via,
    //             'sent_trx_id' => $u->last_sent_trx_id,
    //             'sent_bank_acc' => $u->last_sent_bank_acc,

    //             // this may come as string from DB; frontend can show raw or JSON.parse if needed
    //             'amount_data' => $u->last_amount_data,

    //             'created_at' => $u->last_created_at,
    //             'updated_at' => $u->last_updated_at,
    //         ] : null;

    //         return [
    //             'id' => $u->id,
    //             'name' => $u->name,
    //             'email' => $u->email,
    //             'unique_user_id' => $u->unique_user_id,
    //             'role_id' => $u->role_id,
    //             'phone' => $u->phone,
    //             'role' => $u->role?->name,
    //             'profile_type' => $profileType,
    //             'profile' => $profile,
    //             'availabilities' => $u->availability,
    //             'specialized_at' => $specializedAt,
    //             'created_at' => $u->created_at,

    //             // computed
    //             'total_income' => (float) $u->total_income,
    //             'total_received' => (float) $u->total_received,
    //             'amount_due' => (float) $u->amount_due,

    //             // quick fields for list display
    //             'last_trx_datetime' => $u->last_trx_datetime,
    //             'last_trx_id' => $u->last_trx_id,

    //             // ✅ full last record for details view
    //             'last_payment_record' => $lastPaymentRecord,
    //         ];
    //     })->values();

    //     return response()->json([
    //         'providers' => $providers,
    //     ]);
    // }


    public function providersPaymentSummary(Request $request)
    {
        $userIdFilter   = $request->query('user_id'); // optional for modal
        $onlyWithIncome = (int) $request->query('only_with_income', 0);

       
        $globalTotalIncome = (float) DB::table('appointments')
            ->where('status', 'completed')
            ->sum('price');

        $globalTotalReceived = (float) DB::table('payment_records')
            ->sum('sent_amount');

        $globalTotalDue = $globalTotalIncome - $globalTotalReceived;

      

      
        $incomeSub = DB::table('appointments')
            ->selectRaw('provider_id as user_id, COALESCE(SUM(price),0) as total_income')
            ->where('status', 'completed')
            ->groupBy('provider_id');

       
        $receivedSub = DB::table('payment_records')
            ->selectRaw('user_id, COALESCE(SUM(sent_amount),0) as total_received')
            ->groupBy('user_id');

        
        $lastPaymentSub = DB::table('payment_records as pr')
            ->select([
                'pr.user_id',
                'pr.id as last_payment_id',
                'pr.trx_datetime as last_trx_datetime',
                'pr.trx_id as last_trx_id',
                'pr.amount_received_datetime as last_amount_received_datetime',
                'pr.sent_amount as last_sent_amount',
                'pr.sent_datetime as last_sent_datetime',
                'pr.sent_via as last_sent_via',
                'pr.sent_trx_id as last_sent_trx_id',
                'pr.sent_bank_acc as last_sent_bank_acc',
                'pr.amount_data as last_amount_data',
                'pr.created_at as last_created_at',
                'pr.updated_at as last_updated_at',
            ])
            ->whereIn('pr.id', function ($q) {
                $q->selectRaw('MAX(id)')
                    ->from('payment_records')
                    ->groupBy('user_id');
            });

      
        $usersQuery = User::query()
            ->select('users.*')
            ->with([
                'role',
                'availability',
                'doctorProfile' => fn($q) => $q->with(['doctorType', 'doctorSpeciality', 'doctorTitle']),
                'lawyerProfile' => fn($q) => $q->with(['lawyerTitle', 'lawyerSpeciality']),
                'commonProfile' => fn($q) => $q->with(['commonSpeciality', 'uniqueIdentification']),
            ])
            ->whereHas('role', function ($q) {
                $q->whereNotIn(DB::raw('LOWER(name)'), [
                    'customer',
                    'moderator',
                    'super admin',
                    'super_admin'
                ]);
            })
            ->leftJoinSub($incomeSub, 'income', fn($j) => $j->on('income.user_id', '=', 'users.id'))
            ->leftJoinSub($receivedSub, 'received', fn($j) => $j->on('received.user_id', '=', 'users.id'))
            ->leftJoinSub($lastPaymentSub, 'lastpay', fn($j) => $j->on('lastpay.user_id', '=', 'users.id'))
            ->addSelect([
                DB::raw('COALESCE(income.total_income, 0) as total_income'),
                DB::raw('COALESCE(received.total_received, 0) as total_received'),
                DB::raw('(COALESCE(income.total_income, 0) - COALESCE(received.total_received, 0)) as amount_due'),

                'lastpay.last_payment_id',
                'lastpay.last_trx_datetime',
                'lastpay.last_trx_id',
                'lastpay.last_amount_received_datetime',
                'lastpay.last_sent_amount',
                'lastpay.last_sent_datetime',
                'lastpay.last_sent_via',
                'lastpay.last_sent_trx_id',
                'lastpay.last_sent_bank_acc',
                'lastpay.last_amount_data',
                'lastpay.last_created_at',
                'lastpay.last_updated_at',
            ]);

        if ($userIdFilter) {
            $usersQuery->where('users.id', (int) $userIdFilter);
        }

        if ($onlyWithIncome === 1) {
            $usersQuery->whereRaw('COALESCE(income.total_income, 0) > 0');
        }

        $users = $usersQuery->get();

        
        $providers = $users->map(function ($u) {

            if ($u->doctorProfile) {
                $profileType = $u->role?->name;
                $profile = $u->doctorProfile;
            } elseif ($u->lawyerProfile) {
                $profileType = $u->role?->name;
                $profile = $u->lawyerProfile;
            } elseif ($u->commonProfile) {
                $profileType = $u->role?->name;
                $profile = $u->commonProfile;
            } else {
                $profileType = 'none';
                $profile = null;
            }

            $specializedAt = match (strtolower((string) $profileType)) {
                'doctor' => $profile?->doctorSpeciality?->specialized_at,
                'lawyer' => $profile?->lawyerSpeciality?->specialized_at,
                default => $profile?->commonSpeciality?->specialized_at,
            };

            return [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone,
                'unique_user_id' => $u->unique_user_id,
                'role' => $u->role?->name,
                'specialized_at' => $specializedAt,

                'total_income' => (float) $u->total_income,
                'total_received' => (float) $u->total_received,
                'amount_due' => (float) $u->amount_due,

                'last_payment_record' => $u->last_payment_id ? [
                    'id' => $u->last_payment_id,
                    'trx_datetime' => $u->last_trx_datetime,
                    'trx_id' => $u->last_trx_id,
                    'amount_received_datetime' => $u->last_amount_received_datetime,
                    'sent_amount' => $u->last_sent_amount,
                    'sent_datetime' => $u->last_sent_datetime,
                    'sent_via' => $u->last_sent_via,
                    'sent_trx_id' => $u->last_sent_trx_id,
                    'sent_bank_acc' => $u->last_sent_bank_acc,
                    'amount_data' => $u->last_amount_data,
                ] : null,
            ];
        })->values();

       
        return response()->json([
            'summary' => [
                'total_income' => round($globalTotalIncome, 2),
                'total_received' => round($globalTotalReceived, 2),
                'total_due' => round($globalTotalDue, 2),
            ],
            'providers' => $providers,
        ]);
    }


    public function update(UpdatePaymentRecordRequest $request, PaymentRecord $paymentRecord): JsonResponse
    {
        $data = $request->validated();

       
        if ($data['sent_via'] === 'MFS') {
            $data['sent_bank_acc'] = null;
        } else { // BANK
            $data['sent_trx_id'] = null;
        }

        $data['trx_datetime'] = Carbon::parse($data['trx_datetime']);
        $data['amount_received_datetime'] = Carbon::parse($data['amount_received_datetime']);
        $data['sent_datetime'] = Carbon::parse($data['sent_datetime']);

        return DB::transaction(function () use ($paymentRecord, $data) {
            $userId = (int) $paymentRecord->user_id;

            
            $latestId = PaymentRecord::where('user_id', $userId)->max('id');
            if ((int) $paymentRecord->id !== (int) $latestId) {
                return response()->json([
                    'message' => 'Only the last payment record can be edited.',
                ], 422);
            }

            
            $totalIncome = (float) DB::table('appointments')
                ->where('provider_id', $userId)
                ->where('status', 'completed')
                ->sum('price');

            
            $amountReceivedBefore = (float) PaymentRecord::where('user_id', $userId)
                ->where('id', '!=', $paymentRecord->id)
                ->sum('sent_amount');

            
            $amountDueBefore = $totalIncome - $amountReceivedBefore;

            
            $sentAmount = (float) $data['sent_amount'];
            if ($sentAmount > $amountDueBefore) {
                return response()->json([
                    'message' => 'Sent amount exceeds current due.',
                    'errors' => [
                        'sent_amount' => ["Max allowed is " . number_format($amountDueBefore, 2, '.', '') . " for this provider (current due)."],
                    ],
                ], 422);
            }

            
            $data['amount_data'] = [
                'history' => [
                    'total_income' => round($totalIncome, 2),
                    'amount_received' => round($amountReceivedBefore, 2),
                    'amount_due' => round($amountDueBefore, 2),
                    'snapshot_at' => now()->toDateTimeString(),
                ],
            ];

            $paymentRecord->update($data);

            return response()->json([
                'message' => 'Payment record updated successfully.',
                'data' => $paymentRecord->fresh(),
            ], 200);
        });
    }


}
