<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DoctorProfileCOntroller extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        

        // Validate the incoming request
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|regex:/^01[3-9][0-9]{8}$/',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'doctor_type_id' => 'required|exists:doctor_types,id',
            'doctor_speciality_id' => 'required|exists:doctor_specialities,id',
            'doctor_title_id' => 'required|exists:doctor_titles,id',
            'bio' => 'nullable|string',
            'pricing' => 'nullable|numeric|min:0',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date|before:today',
            'district' => 'nullable|string|max:255',
            'thana' => 'nullable|string|max:255',
            'identification_no' => 'required|string|max:255',
            'registration_no' => 'required|string|max:255',
            'active_from' => 'nullable|date_format:H:i',
            'active_to' => 'nullable|date_format:H:i|after:active_from',
        ]);

    


        DB::beginTransaction();
        try {
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
            $user->doctorProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $validated
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating profile: ' . $e->getMessage()], 500);
        }

 

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    public function show(Request $request)
    {
        // Retrieve the authenticated user
        $user = $request->user();

        // Load the associated customer profile
        $user->load([
            'doctorProfile',
            'doctorProfile.doctorType',
            'doctorProfile.doctorSpeciality',
            'doctorProfile.doctorTitle'
        ]);

        // Return the user data along with the customer profile
        return response()->json([
            'success' => true,
            'message' => 'Doctor profile retrieved successfully.',
            'code' => 200,
            'status' => true,
            'data' => $user,
            // 'doctor_profile' => $user->doctorProfile,
          
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

        $request->avatar->move(public_path('images/doctor'), $imageName);

        // Generate full URL
        $imageUrl = asset('images/doctor/' . $imageName); // or asset('images/' . $imageName)

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
        if ($user->avatar) {
            // Convert full URL to relative path
            $relativePath = str_replace(asset('') . '/', '', $user->avatar);

            // Check if the file exists and delete it
            if (File::exists(public_path($relativePath))) {
                File::delete(public_path($relativePath));
            }
        }

        $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

        $request->avatar->move(public_path('images/doctor'), $imageName);

        // Generate full URL
        $imageUrl = asset('images/doctor/' . $imageName); // or asset('images/' . $imageName)

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

        // Find the doctor profile by user_id
        $doctorProfile = DoctorProfile::where('user_id', auth()->user()->id)->first();

        if (!$doctorProfile) {
            return response()->json(['message' => 'Doctor profile not found'], 404);
        }

        // Update pricing
        $doctorProfile->update([
            'pricing' => $request->pricing,
        ]);

        return response()->json([
            'message' => 'Doctor pricing updated successfully',
            'data' => $doctorProfile
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $request->validate([
            'availability' => 'required|boolean',
        ]);

        // Find the doctor profile by user_id
        $doctorProfile = DoctorProfile::where('user_id', auth()->user()->id)->first();

        if (!$doctorProfile) {
            return response()->json(['message' => 'Doctor profile not found'], 404);
        }

        // Update availability status
        $doctorProfile->update([
            'availability' => $request->availability,
        ]);

        return response()->json([
            'message' => 'Doctor availability updated successfully',
            'data' => $doctorProfile
        ]);
    }
}