<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Promocode;
use App\Models\PromocodeAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PromocodeController extends Controller
{
    public function store(Request $request)
    {

        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        $data = $request->validate([
            'code' => 'required|unique:promocodes,code',
            'discount' => 'required|numeric',
            'discount_type' => 'required|in:amount,percent',
            'valid_from' => 'nullable|date_format:Y-m-d H:i:s',
            'valid_to' => 'nullable|date_format:Y-m-d H:i:s',
            'is_active' => 'nullable|boolean',
        ]);

        $promocode = Promocode::create($data);

        return response()->json([
            'message' => 'Promocode created successfully',
            'promocode' => $promocode
        ]);
    }

    public function show($id)
    {

        $promocode = Promocode::findOrFail($id);


        return response()->json([
            'promocode' => $promocode
        ]);
    }

    public function update(Request $request, $id)
    {

        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        $data = $request->validate([
            'is_active' => 'nullable|boolean',
            'discount' => 'nullable|numeric',
            'discount_type' => 'nullable|in:amount,percent',
            'valid_from' => 'nullable|date_format:Y-m-d H:i:s',
            'valid_to' => 'nullable|date_format:Y-m-d H:i:s',
        ]);


        $promocode = Promocode::findOrFail($id);


        $promocode->update($data);


        return response()->json([
            'message' => 'Promocode updated successfully',
            'promocode' => $promocode
        ]);
    }

    public function statusUpdate(Request $request, $id)
    {

        $request->merge([
            'is_active' => filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN)
        ]);

        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $promocode = Promocode::findOrFail($id);


        $promocode->update($data);


        return response()->json([
            'message' => 'Promocode status updated successfully',
            'promocode' => $promocode
        ]);
    }

    public function destroy($id)
    {

        $promocode = Promocode::findOrFail($id);


        $promocode->delete();


        return response()->json([
            'message' => 'Promocode deleted successfully'
        ]);
    }


    // public function assign(Request $request)
    // {
    //     $validated = $request->validate([
    //         'promocode_id'   => 'required|exists:promocodes,id',
    //         'user_id'        => 'nullable|exists:users,id',
    //         'role_id'        => 'nullable|exists:roles,id',
    //         'speciality_id'  => 'nullable|integer',

    //     ]);


    //     $userId = $validated['user_id'] ?? null;
    //     $roleId = $validated['role_id'] ?? null;
    //     $specialityId = $validated['speciality_id'] ?? null;


    //     if ($userId) {
    //         if ($roleId || $specialityId) {
    //             return response()->json([
    //                 'message' => 'When assigning to user, role and speciality must be null.'
    //             ], 422);
    //         }

    //         $assignment = PromocodeAssignment::create([
    //             'promocode_id' => $validated['promocode_id'],
    //             'user_id' => $userId
    //         ]);
    //     } elseif ($roleId && !$specialityId) {
    //         $assignment = PromocodeAssignment::create([
    //             'promocode_id' => $validated['promocode_id'],
    //             'role_id' => $roleId
    //         ]);
    //     } elseif ($roleId && $specialityId) {
    //         $role = Role::find($roleId);
    //         if (!$role) {
    //             return response()->json(['message' => 'Invalid role ID.'], 404);
    //         }

    //         $specialityType = $role->name;

    //         $assignment = PromocodeAssignment::create([
    //             'promocode_id'    => $validated['promocode_id'],
    //             'role_id'         => $roleId,
    //             'speciality_id'   => $specialityId,
    //             'speciality_type' => $specialityType,
    //         ]);
    //     } else {
    //         return response()->json([
    //             'message' => 'Invalid assignment input. You must set either user_id, or role_id, or both role_id and speciality_id.'
    //         ], 422);
    //     }

    //     return response()->json([
    //         'message' => 'Promocode assigned successfully.',
    //         'assignment' => $assignment
    //     ]);
    // }

    // public function assign(Request $request)
    // {
    //     $validated = $request->validate([
    //         'promocode_id'   => 'required|exists:promocodes,id',
    //         'user_id'        => 'nullable|array', 
    //         'user_id.*'       => 'exists:users,id', 
    //         'role_id'         => 'nullable|exists:roles,id',
    //         'speciality_id'   => 'nullable|integer',
    //     ]);

    //     $userIds = $validated['user_id'] ?? null;
    //     $roleId = $validated['role_id'] ?? null;
    //     $specialityId = $validated['speciality_id'] ?? null;


    //     if ($userIds) {

    //         if ($roleId || $specialityId) {
    //             return response()->json([
    //                 'message' => 'When assigning to users, role and speciality must be null.'
    //             ], 422);
    //         }


    //         $assignments = [];
    //         foreach ($userIds as $userId) {
    //             $assignments[] = PromocodeAssignment::create([
    //                 'promocode_id' => $validated['promocode_id'],
    //                 'user_id' => $userId
    //             ]);
    //         }

    //         return response()->json([
    //             'message' => 'Promocode assigned to users successfully.',
    //             'assignments' => $assignments
    //         ]);
    //     }


    //     if ($roleId && !$specialityId) {
    //         $assignment = PromocodeAssignment::create([
    //             'promocode_id' => $validated['promocode_id'],
    //             'role_id' => $roleId
    //         ]);
    //         return response()->json([
    //             'message' => 'Promocode assigned to role successfully.',
    //             'assignment' => $assignment
    //         ]);
    //     }


    //     if ($roleId && $specialityId) {
    //         $role = Role::find($roleId);
    //         if (!$role) {
    //             return response()->json(['message' => 'Invalid role ID.'], 404);
    //         }

    //         $specialityType = $role->name;

    //         $assignment = PromocodeAssignment::create([
    //             'promocode_id'    => $validated['promocode_id'],
    //             'role_id'         => $roleId,
    //             'speciality_id'   => $specialityId,
    //             'speciality_type' => $specialityType,
    //         ]);

    //         return response()->json([
    //             'message' => 'Promocode assigned to role and speciality successfully.',
    //             'assignment' => $assignment
    //         ]);
    //     }

    //     return response()->json([
    //         'message' => 'Invalid assignment input. You must set either user_id, or role_id, or both role_id and speciality_id.'
    //     ], 422);
    // }

    // public function assign(Request $request)
    // {
    //     // super simple validation (arrays or scalars are fine)
    //     $request->validate([
    //         'promocode_id'   => 'required|exists:promocodes,id',
    //         'user_id'        => 'nullable',
    //         'user_id.*'      => 'integer|exists:users,id',
    //         'role_id'        => 'nullable',
    //         'role_id.*'      => 'integer|exists:roles,id',
    //         'speciality_id'  => 'nullable',
    //         'speciality_id.*' => 'integer',
    //     ]);

    //     // normalize to arrays
    //     $promocodeId   = (int) $request->promocode_id;
    //     $userIds       = collect(Arr::wrap($request->user_id))->filter()->unique()->values();
    //     $roleIds       = collect(Arr::wrap($request->role_id))->filter()->unique()->values();
    //     $specialityIds = collect(Arr::wrap($request->speciality_id))->filter()->unique()->values();

    //     // --- mode 1: assign to specific users ---
    //     if ($userIds->isNotEmpty()) {
    //         if ($roleIds->isNotEmpty() || $specialityIds->isNotEmpty()) {
    //             return response()->json(['message' => 'When assigning to users, omit role_id and speciality_id'], 422);
    //         }

    //         foreach ($userIds as $uid) {
    //             PromocodeAssignment::create([
    //                 'promocode_id' => $promocodeId,
    //                 'user_id'      => $uid,
    //             ]);
    //         }

    //         return response()->json(['message' => 'Assigned to users successfully.']);
    //     }

    //     // --- mode 2: assign to roles only ---
    //     if ($roleIds->isNotEmpty() && $specialityIds->isEmpty()) {
    //         foreach ($roleIds as $rid) {
    //             PromocodeAssignment::create([
    //                 'promocode_id' => $promocodeId,
    //                 'role_id'      => $rid,
    //             ]);
    //         }

    //         return response()->json(['message' => 'Assigned to roles successfully.']);
    //     }

    //     // --- mode 3: assign to role + speciality (every role x every speciality) ---
    //     if ($roleIds->isNotEmpty() && $specialityIds->isNotEmpty()) {
    //         // fetch role names once to fill speciality_type (e.g., 'doctor', 'lawyer', etc.)
    //         $roles = Role::whereIn('id', $roleIds)->pluck('name', 'id');

    //         foreach ($roleIds as $rid) {
    //             $specialityType = $roles[$rid] ?? null;

    //             foreach ($specialityIds as $sid) {
    //                 PromocodeAssignment::create([
    //                     'promocode_id'    => $promocodeId,
    //                     'role_id'         => $rid,
    //                     'speciality_id'   => $sid,
    //                     'speciality_type' => $specialityType,
    //                 ]);
    //             }
    //         }

    //         return response()->json(['message' => 'Assigned to roles + specialities successfully.']);
    //     }

    //     // nothing valid was provided
    //     return response()->json([
    //         'message' => 'Provide user_id(s) OR role_id(s). If speciality_id is provided, it must be with role_id.'
    //     ], 422);
    // }

    public function assign(Request $request)
    {
        // Is the request trying to assign by speciality?
        $hasSpeciality = filled($request->input('speciality_id'));

        if ($hasSpeciality) {
            // ---- MODE A: role + speciality (role_id is a SINGLE required int; speciality_id can be scalar or array)
            $data = $request->validate([
                'promocode_id'    => 'required|exists:promocodes,id',
                'role_id'         => 'required|integer|exists:roles,id', // single role only
                'speciality_id'   => 'required',                          // accept scalar or array
                'speciality_id.*' => 'integer',                           // elements must be ints if array
                'user_id'         => 'prohibited',                        // users not allowed in this mode
            ]);

            $promocodeId   = (int) $data['promocode_id'];
            $roleId        = (int) $data['role_id'];
            $specialityIds = collect(Arr::wrap($data['speciality_id']))->filter()->map('intval')->unique()->values();

            // fetch role name once for speciality_type
            $roleName = Role::whereKey($roleId)->value('name');

            foreach ($specialityIds as $sid) {
                PromocodeAssignment::create([
                    'promocode_id'    => $promocodeId,
                    'role_id'         => $roleId,
                    'speciality_id'   => $sid,
                    'speciality_type' => $roleName, // e.g. 'doctor', 'lawyer', etc.
                ]);
            }

            return response()->json(['message' => 'Assigned to role + specialities successfully.']);
        }

        // ---- MODE B: users OR roles (arrays allowed), but NOT both together
        $data = $request->validate([
            'promocode_id'    => 'required|exists:promocodes,id',
            'user_id'         => 'nullable|array',
            'user_id.*'       => 'integer|exists:users,id',
            'role_id'         => 'nullable|array',
            'role_id.*'       => 'integer|exists:roles,id',
            'speciality_id'   => 'prohibited',  // no speciality here
        ]);

        $promocodeId = (int) $data['promocode_id'];
        $userIds     = collect($data['user_id'] ?? [])->unique()->values();
        $roleIds     = collect($data['role_id'] ?? [])->unique()->values();

        if ($userIds->isNotEmpty() && $roleIds->isNotEmpty()) {
            return response()->json(['message' => 'Provide either user_id[] OR role_id[], not both.'], 422);
        }

        if ($userIds->isNotEmpty()) {
            foreach ($userIds as $uid) {
                PromocodeAssignment::create([
                    'promocode_id' => $promocodeId,
                    'user_id'      => (int) $uid,
                ]);
            }
            return response()->json(['message' => 'Assigned to users successfully.']);
        }

        if ($roleIds->isNotEmpty()) {
            foreach ($roleIds as $rid) {
                PromocodeAssignment::create([
                    'promocode_id' => $promocodeId,
                    'role_id'      => (int) $rid,
                ]);
            }
            return response()->json(['message' => 'Assigned to roles successfully.']);
        }

        return response()->json(['message' => 'Provide user_id[] or role_id[], or use role_id + speciality_id.'], 422);
    }



    public function check(Request $request)
    {
        $data = $request->validate([
            'provider_unique_user_id' => 'required|exists:users,unique_user_id',
            'code' => 'required|exists:promocodes,code'
        ]);

        $user = User::where('unique_user_id', $data['provider_unique_user_id'])->first();
        $promocode = Promocode::where('code', $data['code'])->where('is_active', true)->first();



        if (!$promocode || now()->lt($promocode->valid_from) || now()->gt($promocode->valid_to)) {
            return response()->json(['message' => 'Promocode is not valid at this time.'], 400);
        }

        $roleName = $user->role->name;
        $roleId = $user->role_id;

        // Get pricing from the correct profile table
        $profile = match ($roleName) {
            'doctor' => $user->doctorProfile,
            'lawyer' => $user->lawyerProfile,
            default => $user->commonProfile
        };

        if (!$profile) {
            return response()->json(['message' => 'User profile not found.'], 404);
        }

        $originalPrice = $profile->pricing ?? 0;

        // Determine user's specialty ID and type
        $specialityId = match ($roleName) {
            'doctor' => $profile->doctor_speciality_id,
            'lawyer' => $profile->lawyer_speciality_id,
            default => $profile->common_speciality_id
        };
        $specialityType = $roleName;


        $isAssigned = $promocode->assignments()->where(function ($q) use ($user, $roleId, $specialityId, $specialityType) {
            $q->where('user_id', $user->id)
                ->orWhere(function ($q) use ($roleId) {
                    $q->whereNotNull('role_id')->whereNull('speciality_id')->where('role_id', $roleId);
                })
                ->orWhere(function ($q) use ($roleId, $specialityId, $specialityType) {
                    $q->where('role_id', $roleId)
                        ->where('speciality_id', $specialityId)
                        ->where('speciality_type', $specialityType);
                });
        })->exists();

        if (!$isAssigned) {
            return response()->json(['message' => 'Promocode not applicable for this provider.'], 403);
        }


        $discount = $promocode->discount_type === 'percent'
            ? ($originalPrice * $promocode->discount / 100)
            : min($promocode->discount, $originalPrice);

        $finalPrice = $originalPrice - $discount;

        return response()->json([
            'original_price' => round($originalPrice, 2),
            'discount_amount' => round($discount, 2),
            'final_price' => round($finalPrice, 2),
            'message' => 'Promocode applied successfully.'
        ]);
    }

    public function getAllPromocodes()
    {
        $promocodes = Promocode::with('assignments')->get();

        return response()->json([
            'promocodes' => $promocodes
        ]);
      
    }


}
