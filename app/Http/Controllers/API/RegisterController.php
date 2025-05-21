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
use Illuminate\Support\Str;


class RegisterController extends Controller
{
    public function register(Request $request)
    {

        $request->validate([
            'phone' => 'required|regex:/^01[3-9][0-9]{8}$/|unique:users,phone',
            'password' => 'required|min:6',
            'role' => 'required',
            'name' => 'required|string',
            'email' => 'nullable|email|unique:users,email',
            // Add validation rules for role-specific fields
        ]);


        // $otp = OtpCode::where('phone', $request->phone)
        //     ->where('is_verified', true)
        //     ->where('expires_at', '>', now())
        //     ->first();

        // if (!$otp) {
        //     return response()->json(['message' => 'OTP not verified or expired or phone number got changed'], 403);
        // }



        DB::beginTransaction();

        try {
            $uniqueUserId = $this->generateUniqueUserId();

   

            // Create the user and pass the generated unique_user_id
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email ?? null,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role_id' => $request->role,
                'unique_user_id' => (string)$uniqueUserId, // Ensure unique_user_id is passed here
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


        switch (Str::lower($user->role->name)) {
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
                $this->createCommonProfile($user, $request);
                break;
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

        $user->wallet()->create([
            'balance' => 0, // Initialize the wallet balance to 0
        ]);

        $user->languageState()->create([
            'state' => 'bn', 
        ]);
    }

    private function createDoctorProfile(User $user, Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'doctor_type_id' => 'sometimes|nullable|exists:doctor_types,id',
            'doctor_speciality_id' => 'sometimes|nullable|exists:doctor_specialities,id',
            'doctor_title_id' => 'sometimes|nullable|exists:doctor_titles,id',
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

        // Using null coalescing operator to handle nullable fields
        $bio = $validated['bio'] ?? null;
        $pricing = $validated['pricing'] ?? null;
        $gender = $validated['gender'] ?? null;
        $dob = $validated['dob'] ?? null;
        $district = $validated['district'] ?? null;
        $thana = $validated['thana'] ?? null;
        $identification_no = $validated['identification_no'];
        $registration_no = $validated['registration_no'];
        $active_from = $validated['active_from'] ?? null;
        $active_to = $validated['active_to'] ?? null;

        // Create the doctor profile for the user
        $user->doctorProfile()->create([
            'doctor_type_id' => $validated['doctor_type_id'] ?? null,
            'doctor_speciality_id' => $validated['doctor_speciality_id'] ?? null,
            'doctor_title_id' => $validated['doctor_title_id']  ?? null,
            'bio' => $bio,
            'pricing' => $pricing,
            'gender' => $gender,
            'dob' => $dob,
            'district' => $district,
            'thana' => $thana,
            'identification_no' => $identification_no,
            'registration_no' => $registration_no,
            'active_from' => $active_from,
            'active_to' => $active_to,
        ]);
    }

    private function createLawyerProfile(User $user, Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'lawyer_title_id' => 'sometimes|nullable|exists:lawyer_titles,id',
            'lawyer_speciality_id' => 'sometimes|nullable|exists:lawyer_specialities,id',
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

        // Using null coalescing operator for nullable fields
        $bio = $validated['bio'] ?? null;
        $pricing = $validated['pricing'] ?? null;
        $gender = $validated['gender'] ?? null;
        $dob = $validated['dob'] ?? null;
        $district = $validated['district'] ?? null;
        $thana = $validated['thana'] ?? null;
        $practice_area = $validated['practice_area'] ?? null;
        $identification_no = $validated['identification_no'];
        $bar_registration_no = $validated['bar_registration_no'];
        $active_from = $validated['active_from'] ?? null;
        $active_to = $validated['active_to'] ?? null;

        // Create the lawyer profile for the user
        $user->lawyerProfile()->create([
            'lawyer_title_id' => $validated['lawyer_title_id'] ?? null,
            'lawyer_speciality_id' => $validated['lawyer_speciality_id'] ?? null,
            'bio' => $bio,
            'pricing' => $pricing,
            'gender' => $gender,
            'dob' => $dob,
            'district' => $district,
            'thana' => $thana,
            'practice_area' => $practice_area,
            'identification_no' => $identification_no,
            'bar_registration_no' => $bar_registration_no,
            'active_from' => $active_from,
            'active_to' => $active_to,
        ]);
    }


    private function createCommonProfile(User $user, Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'common_speciality_id' => 'sometimes|nullable|exists:common_provider_specialities,id',
            'bio' => 'nullable|string',
            'pricing' => 'nullable|numeric|min:0',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date|before:today',
            'district' => 'nullable|string|max:255',
            'thana' => 'nullable|string|max:255',
            'identification_no' => 'required|string|max:255',
            'active_from' => 'nullable|date_format:H:i',
            'active_to' => 'nullable|date_format:H:i|after:active_from',
            'unique_identification_no' => 'required|string|max:255',
            'other_data' => 'nullable',  // Optional other data field (JSON or text)
        ]);

        // Use null coalescing to handle missing fields
        $bio = $validated['bio'] ?? null;
        $pricing = $validated['pricing'] ?? null;
        $gender = $validated['gender'] ?? null;
        $dob = $validated['dob'] ?? null;
        $district = $validated['district'] ?? null;
        $thana = $validated['thana'] ?? null;
        $identification_no = $validated['identification_no'];
        $active_from = $validated['active_from'] ?? null;
        $active_to = $validated['active_to'] ?? null;
        $unique_identification_no = $validated['unique_identification_no'];

        // Process `other_data` to ensure it's in JSON format
        $otherData = $validated['other_data'] ?? null;

        if ($otherData) {
            // If `other_data` is an array or object, convert it to JSON
            if (is_array($otherData) || is_object($otherData)) {
                $otherData = json_encode($otherData);
            }

            // Check if it's a valid JSON string
            if (json_decode($otherData) === null && json_last_error() !== JSON_ERROR_NONE) {
                // If it's not valid JSON, wrap it in a JSON object with the 'data' key
                $otherData = json_encode(['data' => $otherData]);
            }
        }

        // Create the common profile for the user
        $commonProfile = $user->commonProfile()->create([
            'common_speciality_id' => $validated['common_speciality_id'] ?? null,
            'bio' => $bio,
            'pricing' => $pricing,
            'gender' => $gender,
            'dob' => $dob,
            'district' => $district,
            'thana' => $thana,
            'identification_no' => $identification_no,
            'active_from' => $active_from,
            'active_to' => $active_to,
        ]);

        // Create the unique identification record for the user
        $commonProfile->uniqueIdentification()->create([
            'unique_identification_no' => $unique_identification_no,
            'other_data' => $otherData,  // Store JSON (either valid or encoded)
        ]);
    }


    private function generateUniqueUserId()
    {
        $uniqueUserId = $this->generateRandomNumber();

        // Check if the unique_user_id already exists in the database
        while (User::where('unique_user_id', $uniqueUserId)->exists()) {
            // Regenerate the random user ID if it already exists
            $uniqueUserId = $this->generateRandomNumber();
        }

        return $uniqueUserId;
    }

    private function generateRandomNumber()
    {
        // You can generate a random number between a range, or use a larger number to make it unique
        return rand(100000000, 999999999);  // Example: Generates a random 9-digit number
    }
}