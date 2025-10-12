<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CommonProfile;
use App\Models\DoctorProfile;
use App\Models\LawyerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class ProviderController extends Controller
{
    public function search(Request $request)
    {
        // Filter by role name (e.g., doctor, lawyer, customer, other)
        $roleId = $request->input('role_id'); // Expected values: doctor, lawyer, etc.

        if (!$roleId) {
            return response()->json(['message' => 'Missing roleId '], 400);
        }

        // Get role_id from role name
        $role = DB::table('roles')->where('id', $roleId)->first();

        if (!$role) {
            return response()->json(['message' => 'Invalid role'], 400);
        }

        // Build base user query for that role
        $userIds = DB::table('users')->where('role_id', $roleId)->pluck('id');

        // Dynamically choose model based on role
        switch (Str::lower($role->name)) {
            case 'doctor':
                $query = DoctorProfile::with('user')->whereIn('user_id', $userIds);
                break;

            case 'lawyer':
                $query = LawyerProfile::with('user')->whereIn('user_id', $userIds);
                break;

            default: // Other Providers
                $query = CommonProfile::with(['user', 'uniqueIdentification'])
                    ->whereIn('user_id', $userIds);
                break;
        }

        // 🔍 Search keyword
        if ($request->filled('q')) {
            $search = $request->q;

            $query->where(function ($q) use ($search, $role) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%$search%"))
                    ->orWhere('bio', 'like', "%$search%");

                if (Str::lower($role->name) === 'doctor') {
                    $q->orWhere('registration_no', 'like', "%$search%");
                } elseif (Str::lower($role->name) === 'lawyer') {
                    $q->orWhere('bar_registration_no', 'like', "%$search%");
                } else {
                    $q->orWhereHas(
                        'uniqueIdentification',
                        fn($uq) =>
                        $uq->where('unique_identification_no', 'like', "%$search%")
                    );
                }
            });
        }

        // 📂 Filters
        if ($request->filled('district')) $query->where('district', $request->district);
        if ($request->filled('gender')) $query->where('gender', $request->gender);
        if ($request->filled('availability')) $query->where('availability', $request->availability);

        if ($request->filled('speciality_id')) {
            if (Str::lower($role->name) === 'doctor') {
                $query->where('doctor_speciality_id', $request->speciality_id);
            } elseif (Str::lower($role->name) === 'lawyer') {
                $query->where('lawyer_speciality_id', $request->speciality_id);
            } else {
                $query->where('common_speciality_id', $request->speciality_id);
            }
        }

        if ($request->filled('price_from') && $request->filled('price_to')) {
            $query->whereBetween('pricing', [$request->price_from, $request->price_to]);
        }

        return response()->json($query,200);
    }
}