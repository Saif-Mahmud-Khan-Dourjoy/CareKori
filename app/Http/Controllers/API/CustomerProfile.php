<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class CustomerProfile extends Controller
{
    // public function update(Request $request)
    // {
    //     $user = $request->user();




    //     // Validate the incoming request
    //     $validated = $request->validate([
    //         'name' => 'sometimes|required|string|max:255',
    //         // 'phone' => 'sometimes|required|regex:/^01[3-9][0-9]{8}$/',
    //         'email' => 'nullable|email|unique:users,email,' . $user->id,
    //         'gender' => 'nullable|in:male,female,other',
    //         'dob' => 'nullable|date',
    //         'district' => 'nullable|string|max:255',
    //         'sub_district' => 'nullable|string|max:255',
    //         'union_name' => 'nullable|string|max:255',
    //         'address' => 'nullable|string|max:500',

    //     ]);

    //     // Update the user's attributes
    //     // if (isset($validated['phone'])) {
    //     //     $user->phone = $validated['phone'];
    //     // }

    //     if (isset($validated['name'])) {
    //         $user->name = $validated['name'];
    //     }

    //     if (isset($validated['email'])) {
    //         $user->email = $validated['email'];
    //     }

    //     $user->save();

    //     // Update or create the customer profile
    //     $user->customerProfile()->updateOrCreate(
    //         ['user_id' => $user->id],
    //         $validated
    //     );

    //     return response()->json(['message' => 'Profile updated successfully.']);
    // }




    // public function show(Request $request)
    // {
    //     // Retrieve the authenticated user
    //     $user = $request->user();

    //     // Load the associated customer profile
    //     $user->load(['customerProfile', 'languageState', 'wallet']);

    //     // Return the user data along with the customer profile
    //     return response()->json([
    //         'user' => $user,
    //         'customer_profile' => $user->customerProfile,
    //     ]);
    // }





    public function update(Request $request)
    {
        $user = $request->user();

        // Validate the incoming request data (excluding the avatar)
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'district' => 'nullable|string|max:255',
            'sub_district' => 'nullable|string|max:255',
            'union_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048', // Avatar validation
        ]);

        // Check if an avatar is uploaded
        if ($request->hasFile('avatar')) {

            // Delete the previous avatar if it exists
            if ($user->customerProfile->avatar) {
                // Convert full URL to relative path
                // $relativePath = str_replace(asset('') . '/', '', $user->customerProfile->avatar);
                $relativePath = str_replace(
                    asset(''),
                    '',
                    $user->customerProfile->avatar
                );




                // return response()->json($relativePath);

                // Check if the file exists and delete it
                if (File::exists(public_path($relativePath))) {

                    File::delete(public_path($relativePath));
                }
            }

            // Generate a unique file name for the new avatar
            $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

            // Move the uploaded image to the 'public/images/customer' directory
            $request->avatar->move(public_path('images/customer'), $imageName);

            // Store the full URL of the uploaded image
            $validated['avatar'] = asset('images/customer/' . $imageName); // Add avatar URL to the validated data
        }

        // Update user details
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        $user->save();

        // Update or create the customer profile, including the avatar URL if it's present
        $user->customerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        // Reload the user with the updated customer profile
        $user->load('customerProfile');

        // Return response
        return response()->json(['message' => 'Profile updated successfully.', 'user' => $user]);
    }
    public function show(Request $request)
    {
        $user = $request->user();
        $user->load(['customerProfile', 'languageState', 'wallet']);

        // Call the controller method
        $response = (new ServiceProvider)->getServiceProvider();

        // Decode the response to get only the 'data'
        $responseData = $response->getData(true); // true = return as array

        return response()->json([
            'user' => $user,
            'services' => $responseData['data'],
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

        $request->avatar->move(public_path('images/customer'), $imageName);

        // Generate full URL
        $imageUrl = asset('images/customer/' . $imageName); // or asset('images/' . $imageName)

        // Update the user's avatar in the database with full URL
        $user->customerProfile()->update(['avatar' => $imageUrl]);

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
        if ($user->customerProfile->avatar) {
            // Convert full URL to relative path
            $relativePath = str_replace(
                asset(''),
                '',
                $user->customerProfile->avatar
            );

            // Check if the file exists and delete it
            if (File::exists(public_path($relativePath))) {
                File::delete(public_path($relativePath));
            }
        }

        $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

        $request->avatar->move(public_path('images/customer'), $imageName);

        // Generate full URL
        $imageUrl = asset('images/customer/' . $imageName); // or asset('images/' . $imageName)

        // Update the user's avatar in the database with full URL
        $user->customerProfile()->update(['avatar' => $imageUrl]);

        return response()->json([
            'message' => 'Profile image Updated successfully.',
            'avatar' => $imageUrl,
            'status' => true,
            'code' => 200
        ], 200);
    }

    public function checkPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.',
                'status' => false,
            ], 401);
        }

        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Password is correct.',
                'status' => true,
            ], 200);
        } else {
            return response()->json([
                'message' => 'Password is incorrect.',
                'status' => false,
            ], 403);
        }
    }
}
