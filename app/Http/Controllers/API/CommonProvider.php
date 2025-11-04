<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CommonProfile;
use App\Models\ProviderWithdrawal;
use App\Models\ServiceProviderAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CommonProvider extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();  // Get the currently authenticated user

        // Validate the incoming request
        $validated = $request->validate([
            'common_speciality_id' => 'sometimes|nullable|exists:common_provider_specialities,id',
            'name' => 'sometimes|required|string|max:255',
           
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'bio' => 'nullable|string',
            'pricing' => 'nullable|numeric|min:0',
           
            'district' => 'nullable|string|max:255',
            'thana' => 'nullable|string|max:255',
            'identification_no' => 'sometimes|required|string|max:255',
            'active_from' => 'nullable|date_format:H:i',
            'active_to' => 'nullable|date_format:H:i|after:active_from',
            'unique_identification_no' => 'sometimes|required|string|max:255',
            'other_data' => 'nullable',  // Optional other data field (JSON or text)
            'address' => 'nullable|string|max:500',
            'avatar' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'bank_name' => 'nullable|string|max:500',
            'account_title' => 'nullable|string|max:500',
            'payment_type' => 'nullable|string|max:500',
            'payment_account' => 'nullable|string|max:500',
            'availabilities' => 'sometimes',
            'division' => 'sometimes|nullable|string|max:255',
            'gender' =>   'sometimes|nullable|string|max:255',
            'dob' => 'sometimes|nullable|date|before:today',
        ]);

        // Update the user's basic details (skip password update if not provided)
        // if (isset($validated['phone'])) {
        //     $user->phone = $validated['phone'];
        // }
        if (isset($validated['division'])) {
            $validated['thana'] = $validated['division'];
            unset($validated['division']);
        }

        
        try {
            DB::beginTransaction();
        if ($request->hasFile('avatar')) {

            // Delete the previous avatar if it exists
            if ($user->commonProfile->avatar) {
                // Convert full URL to relative path
                // $relativePath = str_replace(asset('') . '/', '', $user->commonProfile->avatar);
                $relativePath = str_replace(
                    asset(''),
                    '',
                    $user->commonProfile->avatar
                );




                // return response()->json($relativePath);

                // Check if the file exists and delete it
                if (File::exists(public_path($relativePath))) {

                    File::delete(public_path($relativePath));
                }
            }

            // Generate a unique file name for the new avatar
            $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

            // Move the uploaded image to the 'public/images/common' directory
            $request->avatar->move(public_path('images/{$user->role->name}'), $imageName);

            // Store the full URL of the uploaded image
            $validated['avatar'] = asset('images/{$user->role->name}/' . $imageName); // Add avatar URL to the validated data
        }


        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        $user->save();

        // Use null coalescing to handle missing fields for common profile
        $bio = $validated['bio'] ?? $user->commonProfile->bio;
        $pricing = $validated['pricing'] ?? $user->commonProfile->pricing;
        $gender = $validated['gender'] ?? $user->commonProfile->gender;
        $dob = $validated['dob'] ?? $user->commonProfile->dob;
        $district = $validated['district'] ?? $user->commonProfile->district;
        $thana = $validated['thana'] ?? $user->commonProfile->thana;
        $identification_no = $validated['identification_no'] ?? $user->commonProfile->identification_no;
        $active_from = $validated['active_from'] ?? $user->commonProfile->active_from;
        $active_to = $validated['active_to'] ?? $user->commonProfile->active_to;
        $unique_identification_no = $validated['unique_identification_no'] ?? $user->commonProfile->uniqueIdentification->unique_identification_no;
        $address = $validated['address'] ?? $user->commonProfile->address;
        $avatar = $validated['avatar'] ?? $user->commonProfile->avatar;
        $bank_name = $validated['bank_name'] ?? $user->commonProfile->bank_name;
        $account_title = $validated['account_title'] ?? $user->commonProfile->account_title;
        $payment_type = $validated['payment_type'] ?? $user->commonProfile->payment_type;
        $payment_account = $validated['payment_account'] ?? $user->commonProfile->payment_account;
        



        // Process `other_data` to ensure it's in JSON format
        $otherData = $validated['other_data'] ?? $user->commonProfile->uniqueIdentification->other_data;

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

        // Update the common profile for the user
        $commonProfile = $user->commonProfile;  // Get the related common profile
        if ($commonProfile) {
            $commonProfile->update([
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
                'address' => $address,
                'avatar' => $avatar,
                'bank_name' => $bank_name,
                'account_title' => $account_title,
                'payment_type' => $payment_type,
                'payment_account' => $payment_account,
            ]);
        }

        // Update the unique identification record for the user
        $uniqueIdentification = $commonProfile->uniqueIdentification;  // Get the related unique identification
        if ($uniqueIdentification) {
            $uniqueIdentification->update([
                'unique_identification_no' => $unique_identification_no,
                'other_data' => $otherData,  // Store JSON (either valid or encoded)
            ]);
        }

        if ($request->has('availabilities')) {
            $this->updateProviderAvailability($user, $request);
        }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating profile: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    private function updateProviderAvailability(User $user, Request $request)
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


        if ($validator->fails()) {


            throw new ValidationException($validator);
        }
        ServiceProviderAvailability::where('provider_id', $user->id)->delete();

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


    public function show(Request $request)
    {
        // Retrieve the authenticated user
        $user = $request->user();

        // Load the associated customer profile
        $user->load([
            'availability',
            'commonProfile',
            'commonProfile.uniqueIdentification',

        ]);

        if ($user->commonProfile) {
            $commonProfile = $user->commonProfile->toArray();


            if (array_key_exists('district', $commonProfile)) {
                $commonProfile['division'] = $commonProfile['district'];
                unset($commonProfile['district']);
            }

            if (array_key_exists('thana', $commonProfile)) {
                $commonProfile['district'] = $commonProfile['thana'];
                unset($commonProfile['thana']);
            }


            $user->commonProfile = $commonProfile;
        }

        // $profileKey = $user->role->name . '_profile';

        $providerId = $user->id;


        $totalEarnings = Appointment::where('provider_id', $providerId)
            ->where('status', 'completed')
            ->where('is_money_back', false)
            ->sum('price');


        $last30DaysEarnings = Appointment::where('provider_id', $providerId)
            ->where('status', 'completed')
            ->where('is_money_back', false)
            ->where('updated_at', '>=', Carbon::now()->subDays(30))
            ->sum('price');


        $totalWithdrawn = ProviderWithdrawal::where('provider_id', $providerId)->where('status', 'success')
            ->sum('amount');


        $withdrawals = ProviderWithdrawal::where('provider_id', $providerId)->where('status', 'success')
            ->orderBy('withdrawn_at', 'desc')
            ->get();

        $earningData = [
            'balance' => $totalEarnings - $totalWithdrawn,
            'total_earnings' => $totalEarnings,
            'last_30_days_earnings' => $last30DaysEarnings,
            'total_withdrawn' => $totalWithdrawn,
            'withdrawals' => $withdrawals,
        ];


        $providerOtherInfo = [];

        $completedAppointments = Appointment::where('provider_id', $providerId)
            ->whereRaw('LOWER(status) LIKE ?', ['%complete%'])
            // ->distinct('customer_id')
            ->count('customer_id');

        $providerOtherInfo['customers_count'] = $completedAppointments;
        $providerOtherInfo['average_rating'] = $user->averageRating();
        $providerOtherInfo['review_count'] = $user->reviewCount();
        $providerOtherInfo['experience_count'] = rand(0, 9);
        $providerOtherInfo['average_rating'] = (float) ($providerOtherInfo['average_rating'] ?? 0.0);

        // Return the user data along with the customer profile
        return response()->json([
            'success' => true,
            'message' => $user->role->name .'profile retrieved successfully.',
            'code' => 200,
            'status' => true,
            'data' => $user,
            'earningData' =>  $earningData,
            'providerOtherInfo' => $providerOtherInfo


        ]);

        // Return the user data along with the customer profile
        // return response()->json([
        //     'user' => $user,
        //     // $profileKey => $user->commonProfile,
        // ]);
    }


    public function addProfileImage(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $user = $request->user(); // Assuming user is authenticated

        // Generate a unique file name
        $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

        $request->avatar->move(public_path("images/{$user->role->name}"), $imageName);

        // Generate full URL
        $imageUrl = asset("images/{$user->role->name}/" . $imageName); // or asset('images/' . $imageName)

        // Update the user's avatar in the database with full URL
        $user->doctorProfile()->update(['avatar' => $imageUrl]);

        return response()->json([
            'message' => 'Profile image added successfully.',
            'avatar' => $imageUrl,
            'status' => true,
            'code' => 200
        ], 200);
    }

    // Update profile image
    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $user = $request->user(); // Assuming user is authenticated

        // Delete the old avatar if it exists
        if ($user->commonProfile->avatar) {
            // Convert full URL to relative path
            $relativePath = str_replace(
                asset(''),
                '',
                $user->commonProfile->avatar
            );

            // Check if the file exists and delete it
            if (File::exists(public_path($relativePath))) {
                File::delete(public_path($relativePath));
            }
        }

        $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

        $request->avatar->move(public_path("images/{$user->role->name}"), $imageName);

        // Generate full URL
        $imageUrl = asset("images/{$user->role->name}/" . $imageName); // or asset('images/' . $imageName)

        // Update the user's avatar in the database with full URL
        $user->doctorProfile()->update(['avatar' => $imageUrl]);

        return response()->json([
            'message' => 'Profile image Updated successfully.',
            'avatar' => $imageUrl,
            'status' => true,
            'code' => 200
        ], 200);
    }

    public function updatePricing(Request $request)
    {
        $request->validate([
            'pricing' => 'required|numeric',
        ]);

        // Find the  profile by user_id
        $profile = CommonProfile::where('user_id', auth()->user()->id)->first();

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        // Update pricing
        $profile->update([
            'pricing' => $request->pricing,
        ]);

        return response()->json([
            'message' => 'Pricing updated successfully',
            'data' => $profile
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $request->validate([
            'availability' => 'required|boolean',
        ]);

        // Find the  profile by user_id
        $profile = CommonProfile::where('user_id', auth()->user()->id)->first();

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        // Update availability status
        $profile->update([
            'availability' => $request->availability,
        ]);

        return response()->json([
            'message' => 'Availability updated successfully',
            'data' => $profile
        ]);
    }
}