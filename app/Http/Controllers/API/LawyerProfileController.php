<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LawyerProfile;
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
        ]);

        // Update the user's attributes
        if (isset($validated['phone'])) {
            $user->phone = $validated['phone'];
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
            'lawyerProfile',
            'lawyerProfile.lawyerTitle'
        ]);

        // Return the user data along with the customer profile
        return response()->json([
            'user' => $user,
            'lawyer_profile' => $user->lawyerProfile,
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
        if ($user->avatar) {
            // Convert full URL to relative path
            $relativePath = str_replace(asset('') . '/', '', $user->avatar);

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