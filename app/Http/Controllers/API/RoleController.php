<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class RoleController extends Controller
{
    // public function createRole(Request $request)
    // {
    //     // Ensure the user is authenticated and is a Super Admin
    //     if (!auth()->check() || auth()->user()->role->name !== 'super admin') {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     // Validate the incoming request
    //     $validated = $request->validate([
    //         'name' => 'required|string|unique:roles,name',
    //     ]);

    //     // Create the new role
    //     Role::create(['name' => $validated['name']]);

    //     return response()->json(['message' => 'Role created successfully.', 'role' => $validated['name'], 'status' => true, 'code'=>201], 201);
    // }


    public function createRole(Request $request)
    {
        // Ensure the user is authenticated and is a Super Admin
        if (!auth()->check() || auth()->user()->role->name !== 'super admin') {
            return response()->json([
                'message' => 'Unauthorized action.',
                'status' => false,
                'code' => 403
            ], 403);
        }

        // Manually validate to catch and format errors
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name',
            'icon' => 'nullable|mimes:jpeg,jpg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
                'status' => false,
                'code' => 422
            ], 422);
        }

        // Handle icon upload if present
        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconFile = $request->file('icon');
            $filename =  time() . '.' . $iconFile->getClientOriginalExtension();
            $iconFile->move(public_path('images/icons/role'), $filename);

            // Generate full URL
            $iconPath = asset('images/icons/role/' . $filename); // or asset('images/' . $documentName)
           
        }

      
        // Create the new role
        Role::create(['name' => $request->name ,  'icon' => $iconPath ]);

        return response()->json([
            'message' => 'Role created successfully.',
            'role' => $request->name,
            'status' => true,
            'code' => 201
        ], 201);
    }
}