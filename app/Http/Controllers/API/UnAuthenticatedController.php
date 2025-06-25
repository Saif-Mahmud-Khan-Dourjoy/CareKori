<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UnAuthenticatedController extends Controller
{
    public function getAllRolesOfServiceProviderAndCustomer()
{
    $customer = Role::where('name', 'customer')->first();

    $otherRoles = Role::whereNotIn('name', ['super admin', 'moderator', 'customer'])->get();
    $roles = collect();
    if ($customer) {
        $roles->push($customer);
    }
    $roles = $roles->merge($otherRoles);

    return response()->json([
        'data' => $roles,
        'message' => 'Roles fetched successfully',
        'status' => true,
        'code' => 200
    ], 200);
}


    public function getIdentificationPlaceholder($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return response()->json([
                'message' => 'Role not found',
                'status' => false,
                'code' => 404
            ], 404);
        }

        return response()->json([
            'data' => $role->identification_placeholder,
            'message' => 'Placeholder fetched successfully',
            'status' => true,
            'code' => 200
        ], 200);

        
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^01[3-9][0-9]{8}$/', // Bangladesh phone validation
            'password' => 'required|string|min:6|confirmed', // Ensure password and confirm password match
        ]);

        // Get the user by phone number
        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Update the password in the users table
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password reset successfully.']);
    }
   
    
}
