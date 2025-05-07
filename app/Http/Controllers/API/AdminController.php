<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ModeratorProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
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


    public function getUserWithProfile($userId)
    {

        $loggedInUser = auth()->user()->load('role');

        // Ensure the authenticated user is a Super Admin
        if (!$loggedInUser->hasRole('super admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Retrieve the user along with their profile
        $user = User::with('customerProfile')->findOrFail($userId);

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
    public function updateUser(Request $request, User $user)
    {
        // Super Admin check (optional)
        

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

        return response()->json(['message' => 'User updated successfully.', 'user' => $user,'status'=>true,'code'=>200], 200);
    }

    // Deactivate user
    public function deactivateUser(User $user)
    {
        

        // Deactivate user by setting 'active_status' to false
        $user->update(['active_status' => false]);

        return response()->json(['message' => 'User deactivated successfully.', 'user' => $user, 'status' => true, 'code' => 200], 200);
    }

    // Delete user
    public function deleteUser(User $user)
    {
        

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
    public function deleteModerator($moderatorId)
    {
        // Ensure the authenticated user is a Super Admin
        

        // Find the moderator by ID
        $moderator = User::findOrFail($moderatorId);

        // Delete the moderator
        $moderator->delete();

        return response()->json(['message' => 'Moderator deleted successfully.']);
    }

    public function getSingleModerator($moderatorId)
    {
        // Ensure the authenticated user is a Super Admin
        

        // Retrieve all roles
        $moderator = User::findOrFail($moderatorId);


        return response()->json([
            'moderator' => $moderator,

        ]);
    }
    public function updateModerator(Request $request, $moderatorId)
    {
        // Ensure the authenticated user is a Super Admin
        

        // Find the moderator by ID
        $moderator = User::findOrFail($moderatorId);

        // Update the moderator with the request data
        $moderator->update($request->all());

        return response()->json(['message' => 'Moderator updated successfully.']);
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
}