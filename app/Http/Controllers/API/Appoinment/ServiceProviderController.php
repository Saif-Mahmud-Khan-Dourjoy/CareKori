<?php

namespace App\Http\Controllers\API\Appoinment;

use App\Http\Controllers\Controller;
use App\Models\AppointmentSlot;
use App\Models\ServiceProviderAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ServiceProviderController extends Controller
{


    // public function storeAvailability(Request $request, $uniqueUserId)
    // {
    //     $validated = $request->validate([
    //         'availability_type' => 'required|in:appointment,instant_consultation',
    //         'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
    //         'time_slots' => 'required|array',
    //         'time_slots.*.start_time' => 'required|date_format:H:i',
    //         'time_slots.*.end_time' => 'required|date_format:H:i|after:start_time',
    //     ]);

    //     // Get provider by unique_user_id
    //     $provider = User::findByUniqueUserId($uniqueUserId);

    //     if (!$provider) {
    //         return response()->json(['error' => 'Provider not found'], 404);
    //     }

    //     // Store the provider's availability slots
    //     foreach ($validated['time_slots'] as $timeSlot) {
    //         ServiceProviderAvailability::create([
    //             'provider_id' => $provider->id,
    //             'availability_type' => $validated['availability_type'],
    //             'day' => $validated['day'],
    //             'start_time' => $timeSlot['start_time'],
    //             'end_time' => $timeSlot['end_time'],
    //         ]);
    //     }

    //     return response()->json(['message' => 'Schedule added successfully']);
    // }


    // public function updateAvailability(Request $request, $uniqueUserId)
    // {
    //     $validated = $request->validate([
    //         'availability_type' => 'required|in:appointment,instant_consultation',
    //         'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
    //         'time_slots' => 'required|array',
    //         'time_slots.*.start_time' => 'required|date_format:H:i',
    //         'time_slots.*.end_time' => 'required|date_format:H:i|after:start_time',
    //     ]);

    //     // Get provider by unique_user_id
    //     $provider = User::findByUniqueUserId($uniqueUserId);

    //     if (!$provider) {
    //         return response()->json(['error' => 'Provider not found'], 404);
    //     }

    //     // Find the availability slots for this provider
    //     $availability = ServiceProviderAvailability::where('provider_id', $provider->id)
    //         ->where('day', $validated['day'])
    //         ->first();

    //     // If no availability exists, return an error
    //     if (!$availability) {
    //         return response()->json(['error' => 'No availability found for this provider on this day'], 404);
    //     }

    //     // Delete old time slots before updating
    //     $availability->slots()->delete();

    //     // Add the new time slots for the given day
    //     foreach ($validated['time_slots'] as $timeSlot) {
    //         ServiceProviderAvailability::create([
    //             'provider_id' => $provider->id,
    //             'availability_type' => $validated['availability_type'],
    //             'day' => $validated['day'],
    //             'start_time' => $timeSlot['start_time'],
    //             'end_time' => $timeSlot['end_time'],
    //         ]);
    //     }

    //     return response()->json(['message' => 'Schedule updated successfully']);
    // }

    // Real implementation of storeAvailability method


    // public function storeAvailability(Request $request )
    // {
    //     $validated = $request->validate([
    //         'availability_type' => 'required|in:appointment,instant_consultation',
    //         'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
    //         'slot_duration' => 'required|integer|min:5|max:60',  // Duration for the slot (5 - 60 minutes)
    //         'time_slots' => 'required|array',
    //         'time_slots.*.start_time' => 'required|date_format:H:i',
    //         'time_slots.*.end_time' => 'required|date_format:H:i|after:start_time',
    //     ]);

    //     // Get provider 
    //     $provider = auth()->user();

    //     if (!$provider) {
    //         return response()->json(['error' => 'Provider not found'], 404);
    //     }



    //     // Store the provider's availability with the slot duration
    //     foreach ($validated['time_slots'] as $timeSlot) {
    //         $availability = ServiceProviderAvailability::create([
    //             'provider_id' => $provider->id,
    //             'availability_type' => $validated['availability_type'],
    //             'day' => $validated['day'],
    //             'start_time' => $timeSlot['start_time'],
    //             'end_time' => $timeSlot['end_time'],
    //             'slot_duration' => $validated['slot_duration'],
    //         ]);


    //     }

    //     return response()->json(['message' => 'Schedule added successfully']);
    // }

    // public function storeAvailability(Request $request)
    // {

    //     $provider = auth()->user();

    //     $check = ServiceProviderAvailability::where('provider_id', $provider->id)->exists();

    //     if ($check) {
    //         return response()->json(['error' => 'You have already added your availability'], 400);
    //     }




    //     $validated = $request->validate([
    //         'slot_duration' => 'required|integer|min:5|max:60',  // Global slot duration
    //         'availabilities' => 'required|array',
    //         'availabilities.*.availability_type' => 'required|in:appointment,instant',
    //         'availabilities.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
    //         'availabilities.*.time_slots' => 'required|array',
    //         'availabilities.*.time_slots.*.start_time' => 'required|date_format:H:i',
    //         'availabilities.*.time_slots.*.end_time' => 'required|date_format:H:i|after:availabilities.*.time_slots.*.start_time',
    //     ]);





    //     if (!$provider) {
    //         return response()->json(['error' => 'Provider not found'], 404);
    //     }

    //     foreach ($validated['availabilities'] as $availabilityBlock) {
    //         foreach ($availabilityBlock['time_slots'] as $slot) {
    //             // Use the global slot_duration for each time slot
    //             ServiceProviderAvailability::create([
    //                 'provider_id' => $provider->id,
    //                 'availability_type' => $availabilityBlock['availability_type'],
    //                 'day' => $availabilityBlock['day'],
    //                 'start_time' => $slot['start_time'],
    //                 'end_time' => $slot['end_time'],
    //                 'slot_duration' => $validated['slot_duration'],  // Apply the global slot_duration here
    //             ]);
    //         }
    //     }

    //     return response()->json(['message' => 'Availabilities stored successfully']);
    // }

    // public function storeAvailability(Request $request)
    // {

    //     $provider = auth()->user();

    //     $check = ServiceProviderAvailability::where('provider_id', $provider->id)->exists();

    //     if ($check) {
    //         return response()->json(['error' => 'You have already added your availability'], 400);
    //     }




    //     $validated = $request->validate([

    //         'availabilities' => 'required|array',
    //         'availabilities.*.availability_type' => 'required|in:appointment,instant',
    //         'availabilities.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
    //         'availabilities.*.slot_duration' => 'required|integer|min:5|max:60',
    //         'availabilities.*.time_slots' => 'required|array',
    //         'availabilities.*.time_slots.*.start_time' => 'required|date_format:H:i',
    //         'availabilities.*.time_slots.*.end_time' => 'required|date_format:H:i|after:availabilities.*.time_slots.*.start_time',
    //     ]);





    //     if (!$provider) {
    //         return response()->json(['error' => 'Provider not found'], 404);
    //     }

    //     foreach ($validated['availabilities'] as $availabilityBlock) {
    //         foreach ($availabilityBlock['time_slots'] as $slot) {
    //             // Use the global slot_duration for each time slot
    //             ServiceProviderAvailability::create([
    //                 'provider_id' => $provider->id,
    //                 'availability_type' => $availabilityBlock['availability_type'],
    //                 'day' => $availabilityBlock['day'],
    //                 'start_time' => $slot['start_time'],
    //                 'end_time' => $slot['end_time'],
    //                 'slot_duration' => $availabilityBlock['slot_duration'],  
    //             ]);
    //         }
    //     }

    //     return response()->json(['message' => 'Availabilities stored successfully']);
    // }


    // public function storeAvailability(Request $request)
    // {
    //     $provider = auth()->user();

    //     // Check if the provider already has availability set
    //     $check = ServiceProviderAvailability::where('provider_id', $provider->id)->exists();

    //     if ($check) {
    //         return response()->json(['error' => 'You have already added your availability'], 400);
    //     }

    //     // Validate the incoming data
    //     $validated = $request->validate([
    //         'availabilities' => 'required|array',
    //         'availabilities.*.availability_type' => 'required|in:appointment,instant',
    //         'availabilities.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
    //         'availabilities.*.slot_duration' => 'required|integer|min:5|max:60',
    //         'availabilities.*.time_slots' => 'required|array',
    //         'availabilities.*.time_slots.*.start_time' => 'required|date_format:H:i',
    //         'availabilities.*.time_slots.*.end_time' => 'required|date_format:H:i|after:availabilities.*.time_slots.*.start_time',
    //     ]);

    //     if (!$provider) {
    //         return response()->json(['error' => 'Provider not found'], 404);
    //     }

    //     // Loop through each availability block (day) and time slot
    //     foreach ($validated['availabilities'] as $availabilityBlock) {
    //         foreach ($availabilityBlock['time_slots'] as $slot) {

    //             // Parse the start and end times in Asia/Dhaka timezone
    //             $startTimeInDhaka = Carbon::parse($slot['start_time'], 'Asia/Dhaka'); // Asia/Dhaka timezone
    //             $endTimeInDhaka = Carbon::parse($slot['end_time'], 'Asia/Dhaka'); // Asia/Dhaka timezone

    //             // Convert both start and end times to UTC (Asia/Dhaka is UTC +6)
    //             $startTimeInUTC = $startTimeInDhaka->copy()->setTimezone('UTC');  // Convert to UTC
    //             $endTimeInUTC = $endTimeInDhaka->copy()->setTimezone('UTC');  // Convert to UTC

    //             $splitedStartTimeInDhaka = explode(':', $startTimeInDhaka->toTimeString())[0];
    //             $splitedEndTimeInDhaka = explode(':', $endTimeInDhaka->toTimeString())[0];



    //             $splitedStartTimeInUTC = explode(':', $startTimeInUTC->toTimeString())[0];
    //             $splitedEndTimeInUTC = explode(':', $endTimeInUTC->toTimeString())[0];




    //             // return response()->json([
    //             //     'start_time_in_dhaka' => $splitedStartTimeInDhaka,
    //             //     'end_time_in_dhaka' => $splitedEndTimeInDhaka,
    //             //     'start_time_in_utc' => $splitedStartTimeInUTC,
    //             //     'end_time_in_utc' => $splitedEndTimeInUTC,

    //             // ]);





    //             $startDay = strtolower($availabilityBlock['day']);
    //             $endDay = $startDay;

    //             // If the start time crosses into the previous day in UTC

    //             if (intval($splitedStartTimeInUTC) > intval($splitedStartTimeInDhaka)) {


    //                 // Adjust the start day to the previous day (Sunday)
    //                 $startDay = $this->getPreviousDay($startDay);
    //             }

    //             // If the end time crosses into the previous day in UTC
    //             if (intval($splitedEndTimeInUTC) > intval($splitedEndTimeInDhaka)) {
    //                 // Adjust the end day to the previous day (Sunday)
    //                 $endDay = $this->getPreviousDay($endDay);
    //             }



    //             // If the start time crosses into the previous day but the end time stays on the same day
    //             if ($startDay !== $endDay) {
    //                 // Create a slot for the previous day (previous day of the provider)
    //                 ServiceProviderAvailability::create([
    //                     'provider_id' => $provider->id,
    //                     'availability_type' => $availabilityBlock['availability_type'],
    //                     'day' => $startDay,  // Dynamic start day (previous day)
    //                     'start_time' => $startTimeInUTC->toTimeString(),
    //                     'end_time' => $startTimeInUTC->copy()->endOfDay()->toTimeString(),  // End of the previous day
    //                     'slot_duration' => $availabilityBlock['slot_duration'],
    //                 ]);

    //                 // Create a slot for the current day (provided day)
    //                 ServiceProviderAvailability::create([
    //                     'provider_id' => $provider->id,
    //                     'availability_type' => $availabilityBlock['availability_type'],
    //                     'day' => $endDay,  // Dynamic end day (same day)
    //                     'start_time' => $endTimeInUTC->copy()->startOfDay()->toTimeString(),  // Start of the current day
    //                     'end_time' => $endTimeInUTC->toTimeString(),
    //                     'slot_duration' => $availabilityBlock['slot_duration'],
    //                 ]);
    //             } else {
    //                 // If the start and end times fall on the same day, create one slot for that day
    //                 ServiceProviderAvailability::create([
    //                     'provider_id' => $provider->id,
    //                     'availability_type' => $availabilityBlock['availability_type'],
    //                     'day' => $startDay,  // Dynamic day
    //                     'start_time' => $startTimeInUTC->toTimeString(),
    //                     'end_time' => $endTimeInUTC->toTimeString(),
    //                     'slot_duration' => $availabilityBlock['slot_duration'],
    //                 ]);
    //             }
    //         }
    //     }

    //     return response()->json(['message' => 'Availabilities stored successfully']);
    // }

    public function storeAvailability(Request $request)
    {



        $provider = auth()->user();


        $check = ServiceProviderAvailability::where('provider_id', $provider->id)->exists();

        if ($check) {
            return response()->json(['error' => 'You have already added your availability'], 400);
        }


        $validated = $request->validate([
            'availabilities' => 'required|array',
            'availabilities.*.availability_type' => 'required|in:appointment,instant',
            'availabilities.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'availabilities.*.slot_duration' => 'required|integer|min:5|max:60',
            'availabilities.*.time_slots' => 'required|array',
            'availabilities.*.time_slots.*.start_time' => 'required|date_format:H:i',
            'availabilities.*.time_slots.*.end_time' => 'required|date_format:H:i|after:availabilities.*.time_slots.*.start_time',
        ]);

        if (!$provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }


        foreach ($validated['availabilities'] as $availabilityBlock) {
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
                        'provider_id' => $provider->id,
                        'availability_type' => $availabilityBlock['availability_type'],
                        'day' => $startDay,  
                        'start_time' => $startTimeInUTC->toTimeString(),
                        'end_time' => $startTimeInUTC->copy()->endOfDay()->toTimeString(),  
                        'slot_duration' => $availabilityBlock['slot_duration'],
                    ]);

                   
                    ServiceProviderAvailability::create([
                        'provider_id' => $provider->id,
                        'availability_type' => $availabilityBlock['availability_type'],
                        'day' => $endDay,  
                        'start_time' => $endTimeInUTC->copy()->startOfDay()->toTimeString(),  
                        'end_time' => $endTimeInUTC->toTimeString(),
                        'slot_duration' => $availabilityBlock['slot_duration'],
                    ]);
                } else {
                  
                    ServiceProviderAvailability::create([
                        'provider_id' => $provider->id,
                        'availability_type' => $availabilityBlock['availability_type'],
                        'day' => $startDay,  
                        'start_time' => $startTimeInUTC->toTimeString(),
                        'end_time' => $endTimeInUTC->toTimeString(),
                        'slot_duration' => $availabilityBlock['slot_duration'],
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Availabilities stored successfully']);
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





    private function generateTimeSlots($availability, $startTime, $endTime, $slotDuration)
    {
        $start = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);

        while ($start->lessThan($end)) {
            AppointmentSlot::create([
                'availability_id' => $availability->id,
                'slot_time' => $start->format('H:i'),
                'is_booked' => false,
            ]);

            // Increment the time by the slot duration
            $start->addMinutes($slotDuration);
        }
    }

    // Update provider availability, including custom slot duration
    // public function updateAvailability(Request $request, $uniqueUserId)
    // {
    //     $validated = $request->validate([
    //         'availability_type' => 'required|in:appointment,instant_consultation',
    //         'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
    //         'slot_duration' => 'required|integer|min:5|max:60',  // Duration for the slot (5 - 60 minutes)
    //         'time_slots' => 'required|array',
    //         'time_slots.*.start_time' => 'required|date_format:H:i',
    //         'time_slots.*.end_time' => 'required|date_format:H:i|after:start_time',
    //     ]);

    //     // Get provider by unique_user_id
    //     $provider = User::findByUniqueUserId($uniqueUserId);

    //     if (!$provider) {
    //         return response()->json(['error' => 'Provider not found'], 404);
    //     }

    //     // Find the availability slots for this provider
    //     $availability = ServiceProviderAvailability::where('provider_id', $provider->id)
    //         ->where('day', $validated['day'])
    //         ->first();

    //     if (!$availability) {
    //         return response()->json(['error' => 'No availability found for this provider on this day'], 404);
    //     }

    //     // Delete old time slots before updating
    //     // $availability->slots()->delete();

    //     // Add the new time slots
    //     foreach ($validated['time_slots'] as $timeSlot) {
    //         ServiceProviderAvailability::create([
    //             'provider_id' => $provider->id,
    //             'availability_type' => $validated['availability_type'],
    //             'day' => $validated['day'],
    //             'start_time' => $timeSlot['start_time'],
    //             'end_time' => $timeSlot['end_time'],
    //             'slot_duration' => $validated['slot_duration'],
    //         ]);

    //         // Generate new time slots for the provider's new availability
    //         $this->generateTimeSlots($availability, $timeSlot['start_time'], $timeSlot['end_time'], $validated['slot_duration']);
    //     }

    //     return response()->json(['message' => 'Schedule updated successfully']);
    // }



    public function updateAvailability(Request $request, $availabilityId)
    {
        $validated = $request->validate([
            'availability_type' => 'required|in:appointment,instant_consultation',
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'slot_duration' => 'required|integer|min:5|max:60',  // Duration for the slot (5 - 60 minutes)
            'time_slots' => 'required|array',
            'time_slots.*.start_time' => 'required|date_format:H:i',
            'time_slots.*.end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        // Get provider by unique_user_id (assume the provider is the authenticated user)
        $provider = auth()->user();

        if (!$provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }

        // Find the availability entry for the given provider and day
        $availability = ServiceProviderAvailability::where('provider_id', $provider->id)
            ->where('id', $availabilityId) // Find by availability ID

            ->first();

        if (!$availability) {
            return response()->json(['error' => 'Availability not found for this provider '], 404);
        }

        // Update the availability details
        $availability->update([
            'day' => $validated['day'],
            'start_time' => $validated['time_slots'][0]['start_time'],
            'end_time' => $validated['time_slots'][0]['end_time'],
            'availability_type' => $validated['availability_type'],
            'slot_duration' => $validated['slot_duration'],
        ]);



        return response()->json(['message' => 'Schedule updated successfully']);
    }

    // public function updateAvailability(Request $request, $availabilityId)
    // {
    //     $validated = $request->validate([
    //         'availability_type' => 'required|in:appointment,instant_consultation',
    //         'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
    //         'slot_duration' => 'required|integer|min:5|max:60',
    //         'start_time' => 'required|date_format:H:i',
    //         'end_time' => 'required|date_format:H:i|after:start_time',
    //     ]);

    //     $provider = auth()->user();

    //     if (!$provider) {
    //         return response()->json(['error' => 'Provider not found'], 404);
    //     }

    //     $availability = ServiceProviderAvailability::where('provider_id', $provider->id)
    //         ->where('id', $availabilityId)
    //         ->first();

    //     if (!$availability) {
    //         return response()->json(['error' => 'Availability not found for this provider'], 404);
    //     }

    //     $availability->update([
    //         'availability_type' => $validated['availability_type'],
    //         'day' => $validated['day'],
    //         'slot_duration' => $validated['slot_duration'],
    //         'start_time' => $validated['start_time'],
    //         'end_time' => $validated['end_time'],
    //     ]);

    //     return response()->json(['message' => 'Schedule updated successfully']);
    // }


    public function deleteAvailability($availabilityId)
    {
        // Get provider by unique_user_id (assume the provider is the authenticated user)
        $provider = auth()->user();

        if (!$provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }

        // Find the availability entry for the given provider and day
        $availability = ServiceProviderAvailability::where('provider_id', $provider->id)
            ->where('id', $availabilityId) // Find by availability ID
            ->first();

        if (!$availability) {
            return response()->json(['error' => 'Availability not found for this provider'], 404);
        }

        // Delete the availability entry
        $availability->delete();

        return response()->json(['message' => 'Schedule deleted successfully']);
    }
}