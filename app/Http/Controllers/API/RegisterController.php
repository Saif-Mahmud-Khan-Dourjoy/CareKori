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
use App\Models\ServiceProviderAvailability;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function register(Request $request)
    {


        try {
            $request->validate([
                'phone' => 'required|regex:/^01[3-9][0-9]{8}$/|unique:users,phone',
                'password' => 'required|min:6',
                'role' => 'required',
                'name' => 'required|string',
                'email' => 'nullable|email|unique:users,email',
                // Add validation rules for role-specific fields
            ]);


            $otp = OtpCode::where('phone', $request->phone)
                ->where('is_verified', true)
                // ->where('expires_at', '>', now())
                ->first();

            if (!$otp) {
                return response()->json(['message' => 'OTP not verified or expired or phone number got changed'], 403);
            }


            DB::beginTransaction();



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
            if (!in_array($user->role->name, ['customer', 'super admin', 'moderator'])) {
                $this->createAvailability($user, $request);
            }

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
        $validated = $request->validate([
            'gender' => 'required|in:male,female,other',
            'dob' => 'required',
            'district' => 'required|string',
            'sub_district' => 'required|string',
            //'union_name' => 'required|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Optional avatar field
        ]);

        // Handle avatar upload if provided
        if ($request->hasFile('avatar')) {
            $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

            $request->avatar->move(public_path('images/customer'), $imageName);

            // Generate full URL
            $imageUrl = asset('images/customer/' . $imageName);


            $validated['avatar'] = $imageUrl; // Store the path in the validated data
        } else {
            $validated['avatar'] = null; // Set to null if no avatar is uploaded
        }

        $user->customerProfile()->create([
            'gender' => $request->gender,
            'dob' => $request->dob,
            'district' => $request->district,
            'sub_district' => $request->sub_district,
            'union_name' => $request->union_name ?? 'empty', //$request->union_name,
            'avatar' => $validated['avatar'] ?? null, // Store the avatar URL
            'address' => $request->address ?? null
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
        $request->merge([
            'payment_type' => strtoupper($request->payment_type),
        ]);
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
            'payment_type' => 'nullable|string|max:10|in:MFS,BANK',
            'payment_account' => 'nullable|required_if:payment_type,MFS|required_if:payment_type,BANK|string|max:255',
            'bank_name' => 'nullable|required_if:payment_type,BANK|string|max:255',
            'account_title' => 'nullable|required_if:payment_type,BANK|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);


        // Handle avatar upload if provided
        if ($request->hasFile('avatar')) {
            $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

            $request->avatar->move(public_path('images/doctor'), $imageName);

            // Generate full URL
            $imageUrl = asset('images/doctor/' . $imageName);


            $validated['avatar'] = $imageUrl; // Store the path in the validated data
        } else {
            $validated['avatar'] = null; // Set to null if no avatar is uploaded
        }



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
        $paymentType = $validated['payment_type'] ?? null;
        $paymentAccount = $validated['payment_account'] ?? null;
        $bankName = $validated['bank_name'] ?? null;
        $accountTitle = $validated['account_title'] ?? null;
        $avatar = $validated['avatar'] ?? null;

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
            'payment_type' => $paymentType,
            'payment_account' => $paymentAccount,
            'bank_name' => $bankName,
            'account_title' => $accountTitle,
            'avatar' => $avatar,
            'address' => $request->address ?? null,
        ]);
    }

    private function createLawyerProfile(User $user, Request $request)
    {

        $request->merge([
            'payment_type' => strtoupper($request->payment_type),
        ]);
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
            'payment_type' => 'nullable|string|max:10|in:MFS,BANK',
            'payment_account' => 'nullable|required_if:payment_type,MFS|required_if:payment_type,BANK|string|max:255',
            'bank_name' => 'nullable|required_if:payment_type,BANK|string|max:255',
            'account_title' => 'nullable|required_if:payment_type,BANK|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Optional avatar field
        ]);

        // Handle avatar upload if provided
        if ($request->hasFile('avatar')) {
            $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

            $request->avatar->move(public_path('images/lawyer'), $imageName);

            // Generate full URL
            $imageUrl = asset('images/lawyer/' . $imageName);


            $validated['avatar'] = $imageUrl; // Store the path in the validated data
        } else {
            $validated['avatar'] = null; // Set to null if no avatar is uploaded
        }

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
        $paymentType = $validated['payment_type'] ?? null;
        $paymentAccount = $validated['payment_account'] ?? null;
        $bankName = $validated['bank_name'] ?? null;
        $accountTitle = $validated['account_title'] ?? null;
        $avatar = $validated['avatar'] ?? null; // Store the avatar URL




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
            'payment_type' => $paymentType,
            'payment_account' => $paymentAccount,
            'bank_name' => $bankName,
            'account_title' => $accountTitle,
            'avatar' => $avatar,
            'address' => $request->address ?? null,
        ]);
    }


    private function createCommonProfile(User $user, Request $request)
    {
        $request->merge([
            'payment_type' => strtoupper($request->payment_type),
        ]);

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
            'payment_type' => 'nullable|string|max:10|in:MFS,BANK',
            'payment_account' => 'nullable|required_if:payment_type,MFS|required_if:payment_type,BANK|string|max:255',
            'bank_name' => 'nullable|required_if:payment_type,BANK|string|max:255',
            'account_title' => 'nullable|required_if:payment_type,BANK|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Optional avatar field
        ]);


        // Handle avatar upload if provided
        if ($request->hasFile('avatar')) {
            $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

            $request->avatar->move(public_path("images/{$user->role->name}"), $imageName);

            // Generate full URL
            $imageUrl = asset("images/{$user->role->name}/" . $imageName);


            $validated['avatar'] = $imageUrl; // Store the path in the validated data
        } else {
            $validated['avatar'] = null; // Set to null if no avatar is uploaded
        }

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
        $paymentType = $validated['payment_type'] ?? null;
        $paymentAccount = $validated['payment_account'] ?? null;
        $bankName = $validated['bank_name'] ?? null;
        $accountTitle = $validated['account_title'] ?? null;
        $unique_identification_no = $validated['unique_identification_no'];
        $avatar = $validated['avatar'] ?? null; // Store the avatar URL

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
            'payment_type' => $paymentType,
            'payment_account' => $paymentAccount,
            'bank_name' => $bankName,
            'account_title' => $accountTitle,
            'avatar' => $avatar,
            'address' => $request->address ?? null,
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



    private function createAvailability(User $user, Request $request)
    {
        // Decode the JSON string
        $availabilities = json_decode($request->input('availabilities'), true);

        // Validate the decoded availabilities array
        $validator = Validator::make(
            ['availabilities' => $availabilities], // Wrap the availabilities into an array for validation
            [
                'availabilities' => 'required|array',
                'availabilities.*.availability_type' => 'required|in:appointment,instant_consultation',
                'availabilities.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'availabilities.*.time_slots' => 'required|array|min:1',
                'availabilities.*.time_slots.*.start_time' => 'required|date_format:H:i',
                'availabilities.*.time_slots.*.end_time' => 'required|date_format:H:i|after:availabilities.*.time_slots.*.start_time',
            ],
            [
                // Custom messages
                'availabilities.required' => 'Availabilities field is required.',
                'availabilities.array' => 'Availabilities should be an array.',
                'availabilities.*.availability_type.required' => 'Each availability block must have an availability type.',
                'availabilities.*.availability_type.in' => 'Availability type must be either appointment or instant_consultation.',
                'availabilities.*.day.required' => 'Each availability block must have a day.',
                'availabilities.*.day.in' => 'Day must be one of the following: monday, tuesday, wednesday, thursday, friday, saturday, sunday.',
                'availabilities.*.time_slots.required' => 'Each availability block must include a time_slots field.',
                'availabilities.*.time_slots.array' => 'The time_slots field must be an array.',
                'availabilities.*.time_slots.min' => 'Each availability block must contain at least one time slot.',
                'availabilities.*.time_slots.*.start_time.required' => 'Start time is required for each time slot.',
                'availabilities.*.time_slots.*.start_time.date_format' => 'Start time must be in the format H:i.',
                'availabilities.*.time_slots.*.end_time.required' => 'End time is required for each time slot.',
                'availabilities.*.time_slots.*.end_time.date_format' => 'End time must be in the format H:i.',
                'availabilities.*.time_slots.*.end_time.after' => 'End time must be after the start time.',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // return response()->json(['errors' => $validator->errors()], 422);

            throw new ValidationException($validator);  // This will throw the error and Laravel will automatically return the response

        }
        // Continue processing availabilities if validation passes
        foreach ($availabilities as $availabilityBlock) {
            foreach ($availabilityBlock['time_slots'] as $slot) {

                $carbon = Carbon::now(env('PROVIDER_TIMEZONE', 'Asia/Dhaka'));
                $todayDate = $carbon->format('Y-m-d');
                $startTimeInDhaka = Carbon::createFromFormat('Y-m-d H:i', $todayDate . ' ' . $slot['start_time'], env('PROVIDER_TIMEZONE', 'Asia/Dhaka'));
                $endTimeInDhaka = Carbon::createFromFormat('Y-m-d H:i', $todayDate . ' ' . $slot['end_time'], env('PROVIDER_TIMEZONE', 'Asia/Dhaka'));

                $startTimeInUTC = $startTimeInDhaka->copy()->setTimezone(env('CUSTOMER_TIMEZONE', 'UTC'));
                $endTimeInUTC = $endTimeInDhaka->copy()->setTimezone(env('CUSTOMER_TIMEZONE', 'UTC'));

                $startDay = strtolower($availabilityBlock['day']);
                $endDay = $startDay;


                if ($startTimeInUTC->toDateString() < $startTimeInDhaka->toDateString()) {
                    $startDay = $this->getPreviousDay($startDay);
                }


                if ($endTimeInUTC->toDateString() < $endTimeInDhaka->toDateString()) {
                    $endDay = $this->getPreviousDay($endDay);
                }


                if ($startDay !== $endDay) {

                    ServiceProviderAvailability::create([
                        'provider_id' => $user->id,
                        'availability_type' => $availabilityBlock['availability_type'],
                        'day' => $startDay,
                        'start_time' => $startTimeInUTC->toTimeString(),
                        'end_time' => $startTimeInUTC->copy()->endOfDay()->toTimeString(),
                        'slot_duration' => $availabilityBlock['slot_duration'] ?? env('SLOT_DURATION', 60),
                    ]);


                    ServiceProviderAvailability::create([
                        'provider_id' => $user->id,
                        'availability_type' => $availabilityBlock['availability_type'],
                        'day' => $endDay,
                        'start_time' => $endTimeInUTC->copy()->startOfDay()->toTimeString(),
                        'end_time' => $endTimeInUTC->toTimeString(),
                        'slot_duration' => $availabilityBlock['slot_duration'] ?? env('SLOT_DURATION', 60),
                    ]);
                } else {

                    ServiceProviderAvailability::create([
                        'provider_id' => $user->id,
                        'availability_type' => $availabilityBlock['availability_type'],
                        'day' => $startDay,
                        'start_time' => $startTimeInUTC->toTimeString(),
                        'end_time' => $endTimeInUTC->toTimeString(),
                        'slot_duration' => $availabilityBlock['slot_duration'] ?? env('SLOT_DURATION', 60),
                    ]);
                }
            }
        }

        // return response()->json(['message' => 'Availabilities stored successfully']);

    }


    public function getPreviousDay($currentDay)
    {
        $days = [
            'monday' => 'sunday',
            'tuesday' => 'monday',
            'wednesday' => 'tuesday',
            'thursday' => 'wednesday',
            'friday' => 'thursday',
            'saturday' => 'friday',
            'sunday' => 'saturday',
        ];

        return $days[$currentDay];
    }
}
