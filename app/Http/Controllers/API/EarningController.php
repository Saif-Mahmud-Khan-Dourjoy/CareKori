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







    public function requestWithdrawal(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'nullable|string', // payment method (e.g., bkash)
            'transaction_id' => 'nullable|string',
            'account_details' => 'nullable|string',
        ]);

        $providerId = Auth::id();

        // Check if the provider has enough earnings to withdraw
        $totalEarnings = Appointment::where('provider_id', $providerId)
            ->where('status', 'completed')
            ->where('is_money_back', false)
            ->sum('price');

        $totalWithdrawn = ProviderWithdrawal::where('provider_id', $providerId)
            ->where('status', 'success')
            ->sum('amount');

        if (($totalEarnings - $totalWithdrawn) < $validated['amount']) {
            return response()->json(['error' => 'Insufficient earnings for withdrawal.'], 400);
        }

        $user = Auth::user()->load('role'); // Assuming the role is loaded as part of the user model

        // Default method assignment based on user profile
        $validated['method'] = $validated['method'] ?? null;

        if (!$validated['method']) {
            switch (strtolower($user->role->name)) {
                case 'doctor':
                    if ($user->doctorProfile) {
                        $validated['method'] = $user->doctorProfile->payment_type;
                    }
                    break;
                case 'lawyer':
                    if ($user->lawyerProfile) {
                        $validated['method'] = $user->lawyerProfile->payment_type;
                    }
                    break;
                default:
                    if ($user->commonProfile) {
                        $validated['method'] = $user->commonProfile->payment_type;
                    }
                    break;
            }
        }

        // Create a new withdrawal request
        $withdrawal = ProviderWithdrawal::create([
            'provider_id' => $providerId,
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'transaction_id' => $validated['transaction_id'] ?? null,
            'account_details' => $validated['account_details'] ?? null,
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
        ]);

        $withdrawal = ProviderWithdrawal::findOrFail($id);




        $withdrawal->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Withdrawal status updated successfully.',
            'withdrawal' => $withdrawal,
        ]);
    }

    // Get Earnings and Withdrawals
    public function getEarnings()
    {
        $providerId = Auth::id();

        // All-time earnings (completed + not money back)
        $totalEarnings = Appointment::where('provider_id', $providerId)
            ->where('status', 'completed')
            ->where('is_money_back', false)
            ->sum('price');

        // Last 30 days earnings
        $last30DaysEarnings = Appointment::where('provider_id', $providerId)
            ->where('status', 'completed')
            ->where('is_money_back', false)
            ->where('updated_at', '>=', Carbon::now()->subDays(30))
            ->sum('price');

        // Total withdrawn amount
        $totalWithdrawn = ProviderWithdrawal::where('provider_id', $providerId)->where('status', 'success')
            ->sum('amount');

        // Withdrawals list (latest first)
        $withdrawals = ProviderWithdrawal::where('provider_id', $providerId)->where('status', 'success')
            ->orderBy('withdrawn_at', 'desc')
            ->get();

        return response()->json([
            'total_earnings' => $totalEarnings,
            'last_30_days_earnings' => $last30DaysEarnings,
            'total_withdrawn' => $totalWithdrawn,
            'withdrawals' => $withdrawals,
        ]);
    }
}
