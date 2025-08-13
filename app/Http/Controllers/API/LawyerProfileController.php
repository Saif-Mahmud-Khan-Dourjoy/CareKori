<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\LawyerProfile;
use App\Models\ProviderWithdrawal;
use App\Models\ServiceProviderAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LawyerProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        // Validate the incoming request
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'lawyer_title_id' => 'sometimes|required|exists:lawyer_titles,id',
            'bio' => 'nullable|string',
            'pricing' => 'nullable|numeric|min:0',
          
            'district' => 'nullable|string|max:255',
            'thana' => 'nullable|string|max:255',
            'practice_area' => 'nullable|string|max:255',
            'identification_no' => 'sometimes|required|string|max:255',
            'bar_registration_no' => 'sometimes|required|string|max:255',
            'active_from' => 'nullable|date_format:H:i',
            'active_to' => 'nullable|date_format:H:i|after:active_from',
            'address' => 'nullable|string|max:500',
            'avatar' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'bank_name' => 'nullable|string|max:500',
            'account_title' => 'nullable|string|max:500',
            'payment_type' => 'nullable|string|max:500',
            'payment_account' => 'nullable|string|max:500',
            'availabilities' => 'sometimes',

        ]);

        // Update the user's attributes
        // if (isset($validated['phone'])) {
        //     $user->phone = $validated['phone'];
        // }

        DB::beginTransaction();
        try {

        if ($request->hasFile('avatar')) {

            // Delete the previous avatar if it exists
            if ($user->doctorProfile->avatar) {
                // Convert full URL to relative path
                // $relativePath = str_replace(asset('') . '/', '', $user->doctorProfile->avatar);
                $relativePath = str_replace(
                    asset(''),
                    '',
                    $user->doctorProfile->avatar
                );




                // return response()->json($relativePath);

                // Check if the file exists and delete it
                if (File::exists(public_path($relativePath))) {

                    File::delete(public_path($relativePath));
                }
            }

            // Generate a unique file name for the new avatar
            $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

            // Move the uploaded image to the 'public/images/doctor' directory
            $request->avatar->move(public_path('images/doctor'), $imageName);

            // Store the full URL of the uploaded image
            $validated['avatar'] = asset('images/doctor/' . $imageName); // Add avatar URL to the validated data
        }

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        $user->save();

        // Update or create the customer profile
        $user->lawyerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );
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
            'lawyerProfile',
            'lawyerProfile.lawyerTitle'
        ]);

        if ($user->lawyerProfile) {
            $lawyerProfile = $user->lawyerProfile->toArray();


            if (array_key_exists('district', $lawyerProfile)) {
                $lawyerProfile['division'] = $lawyerProfile['district'];
                unset($lawyerProfile['district']);
            }

            if (array_key_exists('thana', $lawyerProfile)) {
                $lawyerProfile['district'] = $lawyerProfile['thana'];
                unset($lawyerProfile['thana']);
            }


            $user->lawyerProfile = $lawyerProfile;
        }

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
            'message' => 'Lawyer profile retrieved successfully.',
            'code' => 200,
            'status' => true,
            'data' => $user,
            'earningData' =>  $earningData,
            'providerOtherInfo' => $providerOtherInfo


        ]);
    }


    public function addProfileImage(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $user = $request->user(); // Assuming user is authenticated

        // Generate a unique file name
        $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

        $request->avatar->move(public_path('images/lawyer'), $imageName);

        // Generate full URL
        $imageUrl = asset('images/lawyer/' . $imageName); // or asset('images/' . $imageName)

        // Update the user's avatar in the database with full URL
        $user->lawyerProfile()->update(['avatar' => $imageUrl]);

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
        if ($user->lawyerProfile->avatar) {
            // Convert full URL to relative path
            $relativePath = str_replace(
                asset(''),
                '',
                $user->lawyerProfile->avatar
            );

            // Check if the file exists and delete it
            if (File::exists(public_path($relativePath))) {
                File::delete(public_path($relativePath));
            }
        }

        $imageName = time() . '_' . $user->id . '.' . $request->avatar->getClientOriginalExtension();

        $request->avatar->move(public_path('images/lawyer'), $imageName);

        // Generate full URL
        $imageUrl = asset('images/lawyer/' . $imageName); // or asset('images/' . $imageName)

        // Update the user's avatar in the database with full URL
        $user->lawyerProfile()->update(['avatar' => $imageUrl]);

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

        // Find the Lawyer profile by user_id
        $lawyerProfile = LawyerProfile::where('user_id', auth()->user()->id)->first();

        if (!$lawyerProfile) {
            return response()->json(['message' => 'Lawyer profile not found'], 404);
        }

        // Update pricing
        $lawyerProfile->update([
            'pricing' => $request->pricing,
        ]);

        return response()->json([
            'message' => 'Lawyer pricing updated successfully',
            'data' => $lawyerProfile
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $request->validate([
            'availability' => 'required|boolean',
        ]);

        // Find the Lawyer profile by user_id
        $lawyerProfile = LawyerProfile::where('user_id', auth()->user()->id)->first();

        if (!$lawyerProfile) {
            return response()->json(['message' => 'Lawyer profile not found'], 404);
        }

        // Update availability status
        $lawyerProfile->update([
            'availability' => $request->availability,
        ]);

        return response()->json([
            'message' => 'Lawyer availability updated successfully',
            'data' => $lawyerProfile
        ]);
    }
}