<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ModeratorProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function create_moderator(Request $request)
    {


        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:8',
            'gender' => 'nullable|string',
            'dob' => 'nullable|date',
            'avatar' => 'nullable|image',
            'active_status' => 'nullable|boolean',
        ]);

        // Create the user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role_id' => Role::where('name', 'Moderator')->orWhere('name', 'moderator')->first()->id,
        ]);



        // Create the moderator profile
        ModeratorProfile::create([
            'user_id' => $user->id,
            'gender' => $validated['gender'] ?? null,
            'dob' => $validated['dob'] ?? null,
            'avatar' => $validated['avatar'] ?? null,

        ]);

        return response()->json(['message' => 'Moderator created successfully.', 'status' => true, 'code' => 201], 201);
    }


    public function getUserWithProfile($uniqueUserId)
    {
        // // Get the logged-in user
        // $loggedInUser = auth()->user()->load('role');

        // // Ensure the authenticated user is a Super Admin
        // if (!$loggedInUser->hasRole('super admin')) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        // Retrieve the user based on the unique_user_id along with their profile
        $user = User::with('customerProfile')
            ->where('unique_user_id', $uniqueUserId)
            ->firstOrFail();

        return response()->json([
            'user' => $user,
        ]);
    }
    public function getAllUsers()
    {




        // Get all users excluding 'super admin' and 'moderator'
        $users = User::with('customerProfile', 'role')
            ->whereHas('role', function ($query) {
                $query->whereNotIn('name', ['super admin', 'moderator']);
            })
            ->get();

        return response()->json([
            'users' => $users,
        ], 200);
    }
    public function updateUser(Request $request, $uniqueUserId)
    {
        // Super Admin check (optional)

        $user = User::with('customerProfile')
            ->where('unique_user_id', $uniqueUserId)
            ->firstOrFail();


        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'phone' => 'required|regex:/^01[3-9][0-9]{8}$/',
            'password' => 'nullable|string|min:6',
        ]);

        if ($request->has('password')) {
            $validated['password'] = bcrypt($request->password);
        }

       

        // Update user in the 'users' table
        $user->update($validated);

        // Optionally update customer profile if needed
        if ($request->has('gender') || $request->has('dob') || $request->has('district') || $request->has('sub_district') || $request->has('union_name')) {
            $customerProfileData = $request->only(['gender', 'dob', 'district', 'sub_district', 'union_name']);
            $user->customerProfile()->update($customerProfileData);
        }

        return response()->json(['message' => 'User updated successfully.', 'user' => $user, 'status' => true, 'code' => 200], 200);
    }

    // Deactivate user
    public function deactivateUser($uniqueUserId)
    {

        $user = User::with('customerProfile')
            ->where('unique_user_id', $uniqueUserId)
            ->firstOrFail();

        // Deactivate user by setting 'active_status' to false
        $user->update(['active_status' => false]);

        return response()->json(['message' => 'User deactivated successfully.', 'user' => $user, 'status' => true, 'code' => 200], 200);
    }

    // Delete user
    public function deleteUser($uniqueUserId)
    {
        $user = User::with('customerProfile')
            ->where('unique_user_id', $uniqueUserId)
            ->firstOrFail();


        // Delete user and related customer profile
        $user->customerProfile()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.', 'status' => true, 'code' => 200], 200);
    }
    public function getAllModerators()
    {
        // Ensure the authenticated user is a Super Admin


        // Retrieve all moderators with their profiles
        $moderators = User::with('moderatorProfile')->whereHas('role', function ($query) {
            $query->where('name', 'Moderator');
        })->get();

        return response()->json([
            'moderators' => $moderators,

        ]);
    }
    public function deleteModerator($uniqueModeratorId)
    {
        // Ensure the authenticated user is a Super Admin


        // Find the moderator by ID
        $moderator = User::with('moderatorProfile')
            ->where('unique_user_id', $uniqueModeratorId)
            ->firstOrFail();

        // Delete the moderator
        $moderator->moderatorProfile()->delete(); // Delete the moderator profile
        $moderator->delete();

        return response()->json(['message' => 'Moderator deleted successfully.']);
    }

    public function getSingleModerator($uniqueModeratorId)
    {
        // Ensure the authenticated user is a Super Admin


        // Retrieve all roles
        $moderator = User::with('moderatorProfile')
            ->where('unique_user_id', $uniqueModeratorId)
            ->firstOrFail();


        return response()->json([
            'moderator' => $moderator,

        ]);
    }
    // public function updateModerator(Request $request, $uniqueModeratorId)
    // {
    //     // Ensure the authenticated user is a Super Admin


    //     // Find the moderator by ID
    //     $moderator = User::findOrFail($uniqueModeratorId);

    //     // Update the moderator with the request data
    //     $moderator->update($request->all());

    //     return response()->json(['message' => 'Moderator updated successfully.']);
    // }

    public function updateModerator(Request $request, $uniqueModeratorId)
    {
        // Validate the incoming request (exclude password from the validation)
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $uniqueModeratorId, // Ensure unique email except for the current user
            'phone' => 'nullable|string|regex:/^01[3-9][0-9]{8}$/',
            'gender' => 'nullable|string',
            'dob' => 'nullable|date',
            'avatar' => 'nullable|string',
            'active_status' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            // Find the user by unique_user_id
            $user = User::where('unique_user_id', $uniqueModeratorId)->firstOrFail();

            // Update the user fields (skip the password field)
            $user->update( ['name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
            'phone' => $validated['phone'] ?? $user->phone]);

            // If the user has an associated moderator profile, update it
            if ($user->moderatorProfile) {
                $user->moderatorProfile->update([
                    'gender' => $validated['gender'] ?? $user->moderatorProfile->gender,
                    'dob' => $validated['dob'] ?? $user->moderatorProfile->dob,
                    'avatar' => $validated['avatar'] ?? $user->moderatorProfile->avatar,
                    'active_status' => $validated['active_status'] ?? $user->moderatorProfile->active_status,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Moderator updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Update failed', 'details' => $e->getMessage()], 500);
        }
    }

    public function getAllRoles()
    {
        // Ensure the authenticated user is a Super Admin


        // Retrieve all roles
        $roles = Role::all();

        return response()->json([
            'roles' => $roles,

        ]);
    }

    public function getSingleRole($roleId)
    {
        // Ensure the authenticated user is a Super Admin


        // Retrieve all roles
        $role = Role::findOrFail($roleId);

        return response()->json([
            'role' => $role,

        ]);
    }
    public function deleteRole($roleId)
    {
        // Ensure the authenticated user is a Super Admin


        // Find the role by ID
        $role = Role::findOrFail($roleId);

        // Delete the role
        $role->delete();

        return response()->json(['message' => 'Role deleted successfully.']);
    }
    public function updateRole(Request $request, $roleId)
    {
        // Ensure the authenticated user is a Super Admin


        // Find the role by ID
        $role = Role::findOrFail($roleId);

        // Update the role with the request data
        $role->update($request->all());

        return response()->json(['message' => 'Role updated successfully.']);
    }



    public function createServiceProviderRole(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'name' => 'required|string|unique:roles,name', // Role name (doctor, lawyer, etc.)
            'identification_placeholder' => 'nullable|string', // Role-specific placeholder (e.g., license number)
        ]);

        // Create the role with the provided data
        $role = Role::create([
            'name' => $request->name,  // Name of the new role
            'identification_placeholder' => $request->identification_placeholder,  // Additional role-specific field
        ]);

        // Return response with success message and role data
        return response()->json([
            'message' => 'Service provider created successfully.',
            'role' => $role
        ], 201);
    }
}