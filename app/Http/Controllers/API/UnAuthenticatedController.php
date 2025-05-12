<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class UnAuthenticatedController extends Controller
{
    public function getAllRolesOfServiceProviderAndCustomer()
    {
        $roles = Role::whereNotIn('name', ['super admin', 'moderator'])->get();
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
    
}