<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ProviderWithdrawal;
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




        $withdrawal->update([
            'status' => $validated['status'],
            'remark' => $validated['remark'] ?? null,
            'method' => $validated['method'] ?? null,
            'transaction_id' => $validated['transaction_id'] ?? null,
            'account_details' => $validated['account_details'] ?? null,

        ]);

        return response()->json([
            'message' => 'Withdrawal status updated successfully.',
            'withdrawal' => $withdrawal,
        ]);
    }


    public function getEarnings()
    {
        $providerId = Auth::id();


        $totalEarnings = Appointment::where('provider_id', $providerId)
            ->where('status', 'completed')
            ->where('is_money_back', false)
            ->sum('price');


        $last30DaysEarnings = Appointment::where('provider_id', $providerId)
            ->where('status', 'completed')
            ->where('is_money_back', false)
            ->where('updated_at', '>=', Carbon::now()->subDays(30))
            ->sum('price');


        $totalWithdrawn = ProviderWithdrawal::where('provider_id', $providerId)->where('status', 'success')
            ->sum('amount');


        $withdrawals = ProviderWithdrawal::where('provider_id', $providerId)->where('status', 'success')
            ->orderBy('withdrawn_at', 'desc')
            ->get();

        return response()->json([
            'balance' => $totalEarnings - $totalWithdrawn,
            'total_earnings' => $totalEarnings,
            'last_30_days_earnings' => $last30DaysEarnings,
            'total_withdrawn' => $totalWithdrawn,
            'withdrawals' => $withdrawals,
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
