<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\LawyerProfile;
use App\Models\ProviderWithdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LawyerProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        // Validate the incoming request
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|regex:/^01[3-9][0-9]{8}$/',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'lawyer_title_id' => 'required|exists:lawyer_titles,id',
            'bio' => 'nullable|string',
            'pricing' => 'nullable|numeric|min:0',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date|before:today',
            'district' => 'nullable|string|max:255',
            'thana' => 'nullable|string|max:255',
            'practice_area' => 'nullable|string|max:255',
            'identification_no' => 'required|string|max:255',
            'registration_no' => 'required|string|max:255',
            'bar_registration_no' => 'required|string|max:255',
            'active_from' => 'nullable|date_format:H:i',
            'active_to' => 'nullable|date_format:H:i|after:active_from',
            'address' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048', // Avatar validation
        ]);

        // Update the user's attributes
        // if (isset($validated['phone'])) {
        //     $user->phone = $validated['phone'];
        // }

        if ($request->hasFile('avatar')) {

            // Delete the previous avatar if it exists
            if ($user->doctorProfile->avatar) {
                // Convert full URL to relative path
                // $relativePath = str_replace(asset('') . '/', '', $user->doctorProfile->avatar);
                $relativePath = str_replace(
                    asset(''),
                    '',
                    $user->doctorProfile->avatar
                );




                // return response()->json($relativePath);

                // Check if the file exists and delete it
                if (File::exists(public_path($relativePath))) {

                    File::delete(public_path($relativePath));
                }
            }

            // Generate a unique file name for the new avatar
            $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

            // Move the uploaded image to the 'public/images/doctor' directory
            $request->avatar->move(public_path('images/doctor'), $imageName);

            // Store the full URL of the uploaded image
            $validated['avatar'] = asset('images/doctor/' . $imageName); // Add avatar URL to the validated data
        }

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        $user->save();

        // Update or create the customer profile
        $user->lawyerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    public function show(Request $request)
    {
        // Retrieve the authenticated user
        $user = $request->user();

        // Load the associated customer profile
        $user->load([
            'availability',
            'lawyerProfile',
            'lawyerProfile.lawyerTitle'
        ]);

        $providerId = $user->id;


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

        $earningData = [
            'balance' => $totalEarnings - $totalWithdrawn,
            'total_earnings' => $totalEarnings,
            'last_30_days_earnings' => $last30DaysEarnings,
            'total_withdrawn' => $totalWithdrawn,
            'withdrawals' => $withdrawals,
        ];


        $providerOtherInfo = [];

        $completedAppointments = Appointment::where('provider_id', $providerId)
            ->whereRaw('LOWER(status) LIKE ?', ['%complete%'])
            // ->distinct('customer_id')
            ->count('customer_id');

        $providerOtherInfo['customers_count'] = $completedAppointments;
        $providerOtherInfo['average_rating'] = $user->averageRating();
        $providerOtherInfo['review_count'] = $user->reviewCount();
        $providerOtherInfo['experience_count'] = rand(0, 9);
        $providerOtherInfo['average_rating'] = (float) ($providerOtherInfo['average_rating'] ?? 0.0);

        // Return the user data along with the customer profile
        return response()->json([
            'success' => true,
            'message' => 'Lawyer profile retrieved successfully.',
            'code' => 200,
            'status' => true,
            'data' => $user,
            'earningData' =>  $earningData,
            'providerOtherInfo' => $providerOtherInfo


        ]);
    }


    public function addProfileImage(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $user = $request->user(); // Assuming user is authenticated

        // Generate a unique file name
        $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

        $request->avatar->move(public_path('images/lawyer'), $imageName);

        // Generate full URL
        $imageUrl = asset('images/lawyer/' . $imageName); // or asset('images/' . $imageName)

        // Update the user's avatar in the database with full URL
        $user->lawyerProfile()->update(['avatar' => $imageUrl]);

        return response()->json([
            'message' => 'Profile image added successfully.',
            'avatar' => $imageUrl,
            'status' => true,
            'code' => 200
        ], 200);
    }

    // Update profile image
    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $user = $request->user(); // Assuming user is authenticated

        // Delete the old avatar if it exists
        if ($user->lawyerProfile->avatar) {
            // Convert full URL to relative path
            $relativePath = str_replace(
                asset(''),
                '',
                $user->lawyerProfile->avatar
            );

            // Check if the file exists and delete it
            if (File::exists(public_path($relativePath))) {
                File::delete(public_path($relativePath));
            }
        }

        $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

        $request->avatar->move(public_path('images/lawyer'), $imageName);

        // Generate full URL
        $imageUrl = asset('images/lawyer/' . $imageName); // or asset('images/' . $imageName)

        // Update the user's avatar in the database with full URL
        $user->lawyerProfile()->update(['avatar' => $imageUrl]);

        return response()->json([
            'message' => 'Profile image Updated successfully.',
            'avatar' => $imageUrl,
            'status' => true,
            'code' => 200
        ], 200);
    }

    public function updatePricing(Request $request)
    {
        $request->validate([
            'pricing' => 'required|numeric',
        ]);

        // Find the Lawyer profile by user_id
        $lawyerProfile = LawyerProfile::where('user_id', auth()->user()->id)->first();

        if (!$lawyerProfile) {
            return response()->json(['message' => 'Lawyer profile not found'], 404);
        }

        // Update pricing
        $lawyerProfile->update([
            'pricing' => $request->pricing,
        ]);

        return response()->json([
            'message' => 'Lawyer pricing updated successfully',
            'data' => $lawyerProfile
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $request->validate([
            'availability' => 'required|boolean',
        ]);

        // Find the Lawyer profile by user_id
        $lawyerProfile = LawyerProfile::where('user_id', auth()->user()->id)->first();

        if (!$lawyerProfile) {
            return response()->json(['message' => 'Lawyer profile not found'], 404);
        }

        // Update availability status
        $lawyerProfile->update([
            'availability' => $request->availability,
        ]);

        return response()->json([
            'message' => 'Lawyer availability updated successfully',
            'data' => $lawyerProfile
        ]);
    }
}
