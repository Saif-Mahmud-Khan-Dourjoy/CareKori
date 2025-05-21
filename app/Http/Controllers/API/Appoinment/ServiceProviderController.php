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


    public function storeAvailability(Request $request )
    {
        $validated = $request->validate([
            'availability_type' => 'required|in:appointment,instant_consultation',
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'slot_duration' => 'required|integer|min:5|max:60',  // Duration for the slot (5 - 60 minutes)
            'time_slots' => 'required|array',
            'time_slots.*.start_time' => 'required|date_format:H:i',
            'time_slots.*.end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        // Get provider 
        $provider = auth()->user();

        if (!$provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }

       

        // Store the provider's availability with the slot duration
        foreach ($validated['time_slots'] as $timeSlot) {
            $availability = ServiceProviderAvailability::create([
                'provider_id' => $provider->id,
                'availability_type' => $validated['availability_type'],
                'day' => $validated['day'],
                'start_time' => $timeSlot['start_time'],
                'end_time' => $timeSlot['end_time'],
                'slot_duration' => $validated['slot_duration'],
            ]);

           
        }

        return response()->json(['message' => 'Schedule added successfully']);
    }

    // public function storeAvailability(Request $request)
    // {
    //     $validated = $request->validate([
    //         'availabilities' => 'required|array',
    //         'availabilities.*.availability_type' => 'required|in:appointment,instant_consultation',
    //         'availabilities.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
    //         'availabilities.*.slot_duration' => 'required|integer|min:5|max:60',
    //         'availabilities.*.time_slots' => 'required|array',
    //         'availabilities.*.time_slots.*.start_time' => 'required|date_format:H:i',
    //         'availabilities.*.time_slots.*.end_time' => 'required|date_format:H:i|after:availabilities.*.time_slots.*.start_time',
    //     ]);

    //     $provider = auth()->user();

    //     if (!$provider) {
    //         return response()->json(['error' => 'Provider not found'], 404);
    //     }

    //     foreach ($validated['availabilities'] as $availabilityBlock) {
    //         foreach ($availabilityBlock['time_slots'] as $slot) {
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

    // Function to generate slots based on custom duration (e.g., 10 or 15 minutes)
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


    public function deleteAvailability($availabilityId) {
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