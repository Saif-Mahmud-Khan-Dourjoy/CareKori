<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CommonProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CommonProvider extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();  // Get the currently authenticated user

        // Validate the incoming request
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|regex:/^01[3-9][0-9]{8}$/',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'bio' => 'nullable|string',
            'pricing' => 'nullable|numeric|min:0',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date|before:today',
            'district' => 'nullable|string|max:255',
            'thana' => 'nullable|string|max:255',
            'identification_no' => 'required|string|max:255',
            'active_from' => 'nullable|date_format:H:i',
            'active_to' => 'nullable|date_format:H:i|after:active_from',
            'unique_identification_no' => 'required|string|max:255',
            'other_data' => 'nullable',  // Optional other data field (JSON or text)
        ]);

        // Update the user's basic details (skip password update if not provided)
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

        // Use null coalescing to handle missing fields for common profile
        $bio = $validated['bio'] ?? $user->commonProfile->bio;
        $pricing = $validated['pricing'] ?? $user->commonProfile->pricing;
        $gender = $validated['gender'] ?? $user->commonProfile->gender;
        $dob = $validated['dob'] ?? $user->commonProfile->dob;
        $district = $validated['district'] ?? $user->commonProfile->district;
        $thana = $validated['thana'] ?? $user->commonProfile->thana;
        $identification_no = $validated['identification_no'] ?? $user->commonProfile->identification_no;
        $active_from = $validated['active_from'] ?? $user->commonProfile->active_from;
        $active_to = $validated['active_to'] ?? $user->commonProfile->active_to;
        $unique_identification_no = $validated['unique_identification_no'] ?? $user->commonProfile->uniqueIdentification->unique_identification_no;

        // Process `other_data` to ensure it's in JSON format
        $otherData = $validated['other_data'] ?? $user->commonProfile->uniqueIdentification->other_data;

        if ($otherData) {
            // If `other_data` is an array or object, convert it to JSON
            if (is_array($otherData) || is_object($otherData)) {
                $otherData = json_encode($otherData);
            }

            // Check if it's a valid JSON string
            if (json_decode($otherData) === null && json_last_error() !== JSON_ERROR_NONE) {
                // If it's not valid JSON, wrap it in a JSON object with the 'data' key
                $otherData = json_encode(['data' => $otherData]);
            }
        }

        // Update the common profile for the user
        $commonProfile = $user->commonProfile;  // Get the related common profile
        if ($commonProfile) {
            $commonProfile->update([
                'bio' => $bio,
                'pricing' => $pricing,
                'gender' => $gender,
                'dob' => $dob,
                'district' => $district,
                'thana' => $thana,
                'identification_no' => $identification_no,
                'active_from' => $active_from,
                'active_to' => $active_to,
            ]);
        }

        // Update the unique identification record for the user
        $uniqueIdentification = $commonProfile->uniqueIdentification;  // Get the related unique identification
        if ($uniqueIdentification) {
            $uniqueIdentification->update([
                'unique_identification_no' => $unique_identification_no,
                'other_data' => $otherData,  // Store JSON (either valid or encoded)
            ]);
        }

        return response()->json(['message' => 'Profile updated successfully.']);
    }


    public function show(Request $request)
    {
        // Retrieve the authenticated user
        $user = $request->user();

        // Load the associated customer profile
        $user->load([
            'commonProfile',
            'commonProfile.uniqueIdentification',

        ]);

        $profileKey = $user->role->name . '_profile';

        // Return the user data along with the customer profile
        return response()->json([
            'user' => $user,
            $profileKey => $user->commonProfile,
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

        $request->avatar->move(public_path("images/{$user->role->name}"), $imageName);

        // Generate full URL
        $imageUrl = asset("images/{$user->role->name}/" . $imageName); // or asset('images/' . $imageName)

        // Update the user's avatar in the database with full URL
        $user->doctorProfile()->update(['avatar' => $imageUrl]);

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
        if ($user->commonProfile->avatar) {
            // Convert full URL to relative path
            $relativePath = str_replace(
                asset(''),
                '',
                $user->commonProfile->avatar
            );

            // Check if the file exists and delete it
            if (File::exists(public_path($relativePath))) {
                File::delete(public_path($relativePath));
            }
        }

        $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

        $request->avatar->move(public_path("images/{$user->role->name}"), $imageName);

        // Generate full URL
        $imageUrl = asset("images/{$user->role->name}/" . $imageName); // or asset('images/' . $imageName)

        // Update the user's avatar in the database with full URL
        $user->doctorProfile()->update(['avatar' => $imageUrl]);

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

        // Find the  profile by user_id
        $profile = CommonProfile::where('user_id', auth()->user()->id)->first();

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        // Update pricing
        $profile->update([
            'pricing' => $request->pricing,
        ]);

        return response()->json([
            'message' => 'Pricing updated successfully',
            'data' => $profile
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $request->validate([
            'availability' => 'required|boolean',
        ]);

        // Find the  profile by user_id
        $profile = CommonProfile::where('user_id', auth()->user()->id)->first();

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        // Update availability status
        $profile->update([
            'availability' => $request->availability,
        ]);

        return response()->json([
            'message' => 'Availability updated successfully',
            'data' => $profile
        ]);
    }
}
