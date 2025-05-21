<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CommonProfile;
use App\Models\CommonProviderSpeciality;
use App\Models\DoctorSpeciality;
use App\Models\LawyerSpeciality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceProvider extends Controller
{
    public function getServiceProvider()
    {
        $serviceProvider = Role::whereNotIn('name', ['super admin', 'moderator', 'customer'])->get();
        return response()->json([
            'data' => $serviceProvider,
            'message' => 'Service Provider fetched successfully',
            'status' => true,
            'code' => 200
        ], 200);
    }

    public function serviceProviderSpeciality($roleId)
    {
        $serviceProvider = Role::find($roleId);
        if (!$serviceProvider) {
            return response()->json([
                'message' => 'Service Provider not found',
                'status' => false,
                'code' => 404
            ], 404);
        }
        switch ($serviceProvider->name) {
            case 'doctor':
                $speciality = DoctorSpeciality::all();
                break;
            case 'lawyer':
                $speciality = LawyerSpeciality::all();
                break;
            default:
                $speciality = CommonProviderSpeciality::where('category_id', $roleId)->get();
                break;
        }

        return response()->json([
            'data' => $speciality,
            'roleId' => $roleId,
            'message' => 'Service Provider speciality fetched successfully',
            'status' => true,
            'code' => 200
        ], 200);
    }

    public function serviceProviderListBySpeciality($specialityId, $roleId){
        $role= Role::find($roleId);
        switch (Str::lower($role->name)) {
            case 'doctor':
                $serviceProvider = User::where('role_id', $roleId)->whereHas('dotorProfile', function ($query) use ($specialityId) {
                    $query->where('doctor_speciality_id', $specialityId);
                })->get();
                break;
            case 'lawyer':
                $serviceProvider = User::where('role_id', $roleId)->whereHas('lawyerProfile', function ($query) use ($specialityId) {
                    $query->where('lawyer_speciality_id', $specialityId);
                })->get();
                break;
            default:
                $serviceProvider = User::where('role_id', $roleId)
                    ->whereHas('commonProfile', function ($query) use ($specialityId) {
                        $query->where('common_speciality_id', $specialityId);
                    })
                    ->with(['commonProfile.uniqueIdentification']) // Eager load related data
                    ->get();
                break;
        }
        if ($serviceProvider->isEmpty()) {
            return response()->json([
                'message' => 'No service provider found for this speciality',
                'status' => false,
                'code' => 404
            ], 404);
        }
        return response()->json([
            'data' => $serviceProvider,
            'message' => 'Service Provider list fetched successfully',
            'status' => true,
            'code' => 200
        ], 200);
    }
}