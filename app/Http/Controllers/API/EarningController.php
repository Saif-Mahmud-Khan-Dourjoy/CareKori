<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ProviderWithdrawal;
use App\Notifications\CommonNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EarningController extends Controller
{
    // public function requestWithdrawal(Request $request)
    // {
    //     $validated = $request->validate([
    //         'amount' => 'required|numeric|min:1',
    //         'method' => 'nullable|string', // payment method (e.g., bkash)
    //         'transaction_id' => 'nullable|string',
    //         'account_details' => 'nullable|string',
    //     ]);

    //     $providerId = Auth::id();

    //     // Check if the provider has enough earnings to withdraw
    //     $totalEarnings = Appointment::where('provider_id', $providerId)
    //         ->where('status', 'completed')
    //         ->where('is_money_back', false)
    //         ->sum('price');

    //     $totalWithdrawn = ProviderWithdrawal::where('provider_id', $providerId)->where('status','success')
    //         ->sum('amount');

    //     if (($totalEarnings - $totalWithdrawn) < $validated['amount']) {
    //         return response()->json(['error' => 'Insufficient earnings for withdrawal.'], 400);
    //     }

    //     $user= Auth::user()->load('role');

    //     $validated['method']= $validated['method'] ?? null;

    //     if(!$validated['method']){
    //         switch (Str::lower($user->role->name)) {

    //             case 'doctor':
    //                 $validated['method'] = $user->doctorProfile->payment_type;
    //                 break;
    //             case 'lawyer':
    //                 $validated['method'] = $user->lawyerProfile->payment_type;
    //                 break;
    //             default:
    //                 $validated['method'] = $user->commonProfile->payment_type;
    //                 break;
    //         }
    //     }





    //     // Create a new withdrawal request
    //     $withdrawal = ProviderWithdrawal::create([
    //         'provider_id' => $providerId,
    //         'amount' => $validated['amount'],
    //         'method' => $validated['method'],
    //         'transaction_id' => $validated['transaction_id']?? null,
    //         'account_details' => $validated['account_details'] ?? null,
    //         'status' => 'requested',
    //     ]);

    //     return response()->json([
    //         'message' => 'Withdrawal request submitted successfully.',
    //         'withdrawal' => $withdrawal,
    //     ]);
    // }







    // public function requestWithdrawal(Request $request)
    // {
    //     $validated = $request->validate([
    //         'amount' => 'required|numeric|min:1',
    //         'method' => 'nullable|string', // payment method (e.g., bkash)
    //         'transaction_id' => 'nullable|string',
    //         'account_details' => 'nullable|string',
    //     ]);

    //     $providerId = Auth::id();

    //     // Check if the provider has enough earnings to withdraw
    //     $totalEarnings = Appointment::where('provider_id', $providerId)
    //         ->where('status', 'completed')
    //         ->where('is_money_back', false)
    //         ->sum('price');

    //     $totalWithdrawn = ProviderWithdrawal::where('provider_id', $providerId)
    //         ->where('status', 'success')
    //         ->sum('amount');

    //     if (($totalEarnings - $totalWithdrawn) < $validated['amount']) {
    //         return response()->json(['error' => 'Insufficient earnings for withdrawal.'], 400);
    //     }

    //     $user = Auth::user()->load('role'); // Assuming the role is loaded as part of the user model

    //     // Default method assignment based on user profile
    //     $validated['method'] = $validated['method'] ?? null;

    //     if (!$validated['method']) {
    //         switch (strtolower($user->role->name)) {
    //             case 'doctor':
    //                 if ($user->doctorProfile) {
    //                     $validated['method'] = $user->doctorProfile->payment_type;
    //                 }
    //                 break;
    //             case 'lawyer':
    //                 if ($user->lawyerProfile) {
    //                     $validated['method'] = $user->lawyerProfile->payment_type;
    //                 }
    //                 break;
    //             default:
    //                 if ($user->commonProfile) {
    //                     $validated['method'] = $user->commonProfile->payment_type;
    //                 }
    //                 break;
    //         }
    //     }

    //     // Create a new withdrawal request
    //     $withdrawal = ProviderWithdrawal::create([
    //         'provider_id' => $providerId,
    //         'amount' => $validated['amount'],
    //         'method' => $validated['method'],
    //         'transaction_id' => $validated['transaction_id'] ?? null,
    //         'account_details' => $validated['account_details'] ?? null,
    //         'status' => 'requested',
    //     ]);

    //     return response()->json([
    //         'message' => 'Withdrawal request submitted successfully.',
    //         'withdrawal' => $withdrawal,
    //     ]);
    // }

    public function requestWithdrawal(Request $request)
    {


        $providerId = Auth::id();


        $totalEarnings = Appointment::where('provider_id', $providerId)
            ->where('status', 'completed')
            ->where('is_money_back', false)
            ->sum('price');

        $totalWithdrawn = ProviderWithdrawal::where('provider_id', $providerId)
            ->where('status', 'success')
            ->sum('amount');

        if ($totalEarnings == $totalWithdrawn) {
            return response()->json([
                'message' => 'There is no money to withdraw.',
            ]);
        }


        $amount =   $totalEarnings - $totalWithdrawn;



        // Create a new withdrawal request
        $withdrawal = ProviderWithdrawal::create([
            'provider_id' => $providerId,
            'amount' => $amount,
            'status' => 'requested',
        ]);

        return response()->json([
            'message' => 'Withdrawal request submitted successfully.',
            'withdrawal' => $withdrawal,
        ]);
    }





    // Update Withdrawal Status (Admin only)
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:success,failed',
            'remark' => 'nullable|string|max:500',
            'method' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'account_details' => 'nullable|string',
        ]);

        $withdrawal = ProviderWithdrawal::findOrFail($id);

        if(!$withdrawal){
            return response()->json(['error' => 'Withdrawal request not found.'], 404);
        }




        $withdrawal->update([
            'status' => $validated['status'],
            'remark' => $validated['remark'] ?? null,
            'method' => $validated['method'] ?? null,
            'transaction_id' => $validated['transaction_id'] ?? null,
            'account_details' => $validated['account_details'] ?? null,

        ]);
        $user= $withdrawal->provider;
        if($validated['status'] === 'success'){
            
            $user->notify(new CommonNotification($user,  "Withdrawal Successful", "Your withdrawal request of amount {$withdrawal->amount} has been processed successfully."));
        }else{
            
            $user->notify(new CommonNotification($user,  "Withdrawal Failed", "Your withdrawal request of amount {$withdrawal->amount} has been failed. Please contact support."));
        }

        return response()->json([
            'message' => 'Withdrawal status updated successfully.',
            'withdrawal' => $withdrawal,
        ]);
    }


    public function getEarnings()
    {
        $providerId = Auth::id();
      $providerRole=Auth::user()->role_id;


        $totalEarnings = Appointment::where('provider_id', $providerId)
            ->where('status', 'completed')
            ->where('is_money_back', false)
            ->sum('price');


        $last30DaysEarnings = Appointment::where('provider_id', $providerId)
            ->where('status', 'completed')
            ->where('is_money_back', false)
            ->where('appointment_time', '>=', Carbon::now()->subDays(30))
            ->sum('price');


        $totalWithdrawn = ProviderWithdrawal::where('provider_id', $providerId)->where('status', 'success')
            ->sum('amount');


            //
            switch ($providerRole) {
            case '4':
                
      $withdrawals = ProviderWithdrawal::where('provider_id', $providerId)
    ->where('status', 'success')
    ->join('doctor_profiles', 'provider_withdrawals.provider_id', '=', 'doctor_profiles.user_id')
    ->orderBy('withdrawn_at', 'desc')
    ->select(
        'provider_withdrawals.*',
        'doctor_profiles.bank_name', // add the columns you want from doctor_profiles
        'doctor_profiles.account_title'
    )
    ->get();
                break;
            case '6':
                $withdrawals = ProviderWithdrawal::where('provider_id', $providerId)
    ->where('status', 'success')
    ->join('doctor_profiles', 'provider_withdrawals.provider_id', '=', 'lawyer_profiles.user_id')
    ->orderBy('withdrawn_at', 'desc')
    ->select(
        'provider_withdrawals.*',
        'lawyer_profiles.bank_name',  
        'lawyer_profiles.account_title'
    )
    ->get();
                break;
            default:
                $withdrawals = ProviderWithdrawal::where('provider_id', $providerId)
    ->where('status', 'success')
    ->join('common_profiles', 'provider_withdrawals.provider_id', '=', 'lawyer_profiles.user_id')
    ->orderBy('withdrawn_at', 'desc')
    ->select(
        'provider_withdrawals.*',
        'common_profiles.bank_name',  
        'common_profiles.account_title'
    )
    ->get();
                break;
        }
            //
          $paymentReceived = Appointment::join('users', 'appointments.customer_id', '=', 'users.id')
    ->where('appointments.provider_id', $providerId)
    ->where('appointments.status', 'completed')
    ->where('appointments.is_money_back', false)
    ->select('appointments.*', 'users.name as service_getter_name')
    ->get();
            

        return response()->json([
            'balance' => $totalEarnings - $totalWithdrawn,
            'total_earnings' => $totalEarnings,
            'last_30_days_earnings' => number_format((float)$last30DaysEarnings, 2, '.', ''),
            'total_withdrawn' => $totalWithdrawn,
            'withdrawn_amount' => $withdrawals,
            'payment_received'=>$paymentReceived
        ]);
    }


    public function getAllWithdrawRequests()
    {

        // return response()->json(Auth::user()->hasRole('super admin') || Auth::user()->hasRole('moderator'));

        if (!Auth::user()->hasRole('super admin') && !Auth::user()->hasRole('moderator')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }


        $withdrawals = ProviderWithdrawal::with('provider')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'withdrawals' => $withdrawals,
        ]);
    }
}
