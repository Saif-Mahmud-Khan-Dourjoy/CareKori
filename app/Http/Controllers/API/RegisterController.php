<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\CustomerProfile;
use App\Models\DoctorProfile;
use App\Models\LawyerProfile;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


class RegisterController extends Controller
{
    public function register(Request $request)
    {

        $request->validate([
            'phone' => 'required|regex:/^01[3-9][0-9]{8}$/|unique:users,phone',
            'password' => 'required|min:6',
            'role' => 'required',
            'name' => 'required|string',
            // Add validation rules for role-specific fields
        ]);


        // $otp = OtpCode::where('phone', $request->phone)
        //     ->where('is_verified', true)
        //     ->where('expires_at', '>', now())
        //     ->first();

        // if (!$otp) {
        //     return response()->json(['message' => 'OTP not verified or expired'], 403);
        // }



        DB::beginTransaction();

        try {
            $role = Role::firstOrCreate(['name' => $request->role]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email ?? null,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role_id' => $role->id,
            ]);

            $this->createProfile($user, $request);

            DB::commit();

            return response()->json([
                'message' => 'Registration successful',
                'token' => $user->createToken('carekori-token')->plainTextToken,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Registration error: ' . $e->getMessage());  // Log the error
            return response()->json(['error' => 'Registration failed', 'details' => $e->getMessage()], 500); // Return the error details
        }
    }

    private function createProfile(User $user, Request $request)
    {


        switch ($user->role->name) {
            case 'customer':
                $this->createCustomerProfile($user, $request);
                break;
            case 'doctor':
                $this->createDoctorProfile($user, $request);
                break;
            case 'lawyer':
                $this->createLawyerProfile($user, $request);
                break;
            default:
                throw new \Exception("Invalid role specified");
        }
    }

    private function createCustomerProfile(User $user, Request $request)
    {
        $request->validate([
            'gender' => 'required|in:male,female,other',
            'dob' => 'required',
            'district' => 'required|string',
            'sub_district' => 'required|string',
            'union_name' => 'required|string',
        ]);

        $user->customerProfile()->create([
            'gender' => $request->gender,
            'dob' => $request->dob,
            'district' => $request->district,
            'sub_district' => $request->sub_district,
            'union_name' => $request->union_name,
        ]);
    }

    private function createDoctorProfile(User $user, Request $request)
    {
        $validated = $request->validate([
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

        $user->doctorProfile()->create($validated);
    }

    private function createLawyerProfile(User $user, Request $request)
    {
        $validated = $request->validate([
            'lawyer_title_id' => 'required|exists:lawyer_titles,id',
            'bio' => 'nullable|string',
            'pricing' => 'nullable|numeric|min:0',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date|before:today',
            'district' => 'nullable|string|max:255',
            'thana' => 'nullable|string|max:255',
            'practice_area' => 'nullable|string|max:255',
            'identification_no' => 'required|string|max:255',
            'bar_registration_no' => 'required|string|max:255',
            'active_from' => 'nullable|date_format:H:i',
            'active_to' => 'nullable|date_format:H:i|after:active_from',

        ]);

        $user->lawyerProfile()->create($validated);
    }
}
