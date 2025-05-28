<?php

namespace App\Http\Controllers\API\Appoinment;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\ServiceProviderAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{



    // public function bookAppointment(Request $request)
    // {
    //     $validated = $request->validate([
    //         'customer_unique_user_id' => 'required|exists:users,unique_user_id',
    //         'provider_unique_user_id' => 'required|exists:users,unique_user_id',
    //         'appointment_time' => 'required|date_format:Y-m-d H:i:s',
    //     ]);

    //     // Fetch customer and provider using their unique_user_id
    //     $customer = User::with('wallet')->where('unique_user_id', $validated['customer_unique_user_id'])->first();
    //     $provider = User::with(['role', 'doctorProfile', 'lawyerProfile', 'commonProfile'])->where('unique_user_id', $validated['provider_unique_user_id'])->first();

    //     if (!$customer || !$provider) {
    //         return response()->json(['error' => 'Invalid customer or provider'], 400);
    //     }

    //     // Determine pricing based on provider's role
    //     $roleName = $provider->role->name ?? null;
    //     $pricing = null;


    //         switch ($roleName) {
    //             case 'doctor':

    //                 $pricing = $provider->doctorProfile->pricing ?? null;
    //                 break;
    //             case 'lawyer':

    //                 $pricing = $provider->lawyerProfile->pricing ?? null;
    //                 break;
    //             default:

    //                 $pricing = $provider->commonProfile->pricing ?? null;
    //                 break;
    //         }

    //     if (is_null($pricing)) {
    //         return response()->json(['error' => 'Provider pricing not found'], 400);
    //     }

    //     // Check if the customer has enough balance in their wallet
    //     $walletBalance = $customer->wallet->balance ?? 0;

    //     if ($walletBalance < $pricing) {
    //         return response()->json(['error' => 'Insufficient balance'], 400);
    //     }

    //     // Create the appointment
    //     $appointment = Appointment::create([
    //         'customer_id' => $customer->id,
    //         'provider_id' => $provider->id,
    //         'appointment_time' => $validated['appointment_time'],
    //         'status' => 'pending',
    //         'price' => $pricing,
    //     ]);

    //     // Deduct the balance from the customer’s wallet
    //     $customer->wallet->decrement('balance', $pricing);

    //     return response()->json([
    //         'message' => 'Appointment booked successfully',
    //         'appointment' => $appointment
    //     ]);
    // }



    // public function bookAppointment(Request $request)
    // {
    //     $validated = $request->validate([
    //         'customer_unique_user_id' => 'required|exists:users,unique_user_id',
    //         'provider_unique_user_id' => 'required|exists:users,unique_user_id',
    //         'appointment_time' => 'required|date_format:Y-m-d H:i:s',
    //     ]);

    //     // Fetch customer and provider using their unique_user_id
    //     $customer = User::with('wallet')->where('unique_user_id', $validated['customer_unique_user_id'])->first();
    //     $provider = User::with(['role', 'doctorProfile', 'lawyerProfile', 'commonProfile'])->where('unique_user_id', $validated['provider_unique_user_id'])->first();

    //     if (!$customer || !$provider) {
    //         return response()->json(['error' => 'Invalid customer or provider'], 400);
    //     }

    //     // Determine pricing based on provider's role
    //     $roleName = $provider->role->name ?? null;
    //     $pricing = null;

    //     switch ($roleName) {
    //         case 'doctor':
    //             $pricing = $provider->doctorProfile->pricing ?? null;
    //             break;
    //         case 'lawyer':
    //             $pricing = $provider->lawyerProfile->pricing ?? null;
    //             break;
    //         default:
    //             $pricing = $provider->commonProfile->pricing ?? null;
    //             break;
    //     }

    //     if (is_null($pricing)) {
    //         return response()->json(['error' => 'Provider pricing not found'], 400);
    //     }

    //     // Check if the customer has enough balance in their wallet
    //     $walletBalance = $customer->wallet->balance ?? 0;

    //     if ($walletBalance < $pricing) {
    //         return response()->json(['error' => 'Insufficient balance'], 400);
    //     }

    //     // Check if the requested appointment time is within the available slots
    //     $availability = ServiceProviderAvailability::where('provider_id', $provider->id)
    //         ->where('day', Carbon::parse($validated['appointment_time'])->format('l'))
    //         ->first();

    //     if (!$availability) {
    //         return response()->json(['error' => 'Provider is not available on this day'], 400);
    //     }

    //     // Check if the slot is available (not already booked)
    //     $slot = AppointmentSlot::where('availability_id', $availability->id)
    //         ->where('slot_time', Carbon::parse($validated['appointment_time'])->format('H:i'))
    //         ->first();

    //     if (!$slot || $slot->is_booked) {
    //         return response()->json(['error' => 'Selected slot is not available'], 400);
    //     }

    //     // Create the appointment
    //     $appointment = Appointment::create([
    //         'customer_id' => $customer->id,
    //         'provider_id' => $provider->id,
    //         'appointment_time' => $validated['appointment_time'],
    //         'status' => 'pending',
    //         'price' => $pricing,
    //     ]);

    //     // Mark the slot as booked
    //     $slot->update(['is_booked' => true]);

    //     // Deduct the balance from the customer’s wallet
    //     $customer->wallet->decrement('balance', $pricing);

    //     return response()->json([
    //         'message' => 'Appointment booked successfully',
    //         'appointment' => $appointment
    //     ]);
    // }


    public function bookAppointment(Request $request)
    {
        $validated = $request->validate([
            'customer_unique_user_id' => 'required|exists:users,unique_user_id',
            'provider_unique_user_id' => 'required|exists:users,unique_user_id',
            'appointment_time' => 'required|date_format:Y-m-d H:i:s',
        ]);

        // Fetch customer and provider using their unique_user_id
        $customer = User::with('wallet')->where('unique_user_id', $validated['customer_unique_user_id'])->first();
        $provider = User::with(['role', 'doctorProfile', 'lawyerProfile', 'commonProfile'])->where('unique_user_id', $validated['provider_unique_user_id'])->first();

        if (!$customer || !$provider) {
            return response()->json(['error' => 'Invalid customer or provider'], 400);
        }

        // Determine pricing based on provider's role
        $roleName = $provider->role->name ?? null;
        $pricing = null;

        switch ($roleName) {
            case 'doctor':
                $pricing = $provider->doctorProfile->pricing ?? null;
                break;
            case 'lawyer':
                $pricing = $provider->lawyerProfile->pricing ?? null;
                break;
            default:
                $pricing = $provider->commonProfile->pricing ?? null;
                break;
        }

        if (is_null($pricing)) {
            return response()->json(['error' => 'Provider pricing not found'], 400);
        }

        // Check if the customer has enough balance in their wallet
        $walletBalance = $customer->wallet->balance ?? 0;

        if ($walletBalance < $pricing) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        // Check if the requested appointment time is within the available slots
        $availability = ServiceProviderAvailability::where('provider_id', $provider->id)
            // ->where('day', Carbon::parse($validated['appointment_time'])->format('l'))
            ->where('day', strtolower(Carbon::parse($validated['appointment_time'])->format('l')))
            ->first();

        $slotAvailability = Appointment::where('provider_id', $provider->id)
            ->where('appointment_time', Carbon::parse($validated['appointment_time']))
            ->first();

        if (!$availability || $slotAvailability) {
            return response()->json(['error' => 'Provider is not available on this time'], 400);
        }


        // Create the appointment
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'provider_id' => $provider->id,
            'appointment_time' => Carbon::createFromFormat('Y-m-d H:i:s', $validated['appointment_time']),
            'status' => 'pending',
            'price' => $pricing,
        ]);

    

        // Deduct the balance from the customer’s wallet
        $customer->wallet->decrement('balance', $pricing);

        return response()->json([
            'message' => 'Appointment booked successfully',
            'appointment' => $appointment
        ]);
    }
    
    public function getAppointmentsByUser($uniqueUserId)
    {
        // Fetch user by unique_user_id
        $user = User::findByUniqueUserId($uniqueUserId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $appointments = Appointment::where('customer_id', $user->id)
            // ->orWhere('provider_id', $user->id)
            ->get();

        return response()->json(['appointments' => $appointments]);
    }

    public function getAppointmentsByProvider($uniqueUserId)
    {
        // Fetch provider by unique_user_id
        $provider = User::findByUniqueUserId($uniqueUserId);

        if (!$provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }

        $appointments = Appointment::where('provider_id', $provider->id)->get();

        return response()->json(['appointments' => $appointments]);
    }



    // public function updateAppointment(Request $request, $appointmentId)
    // {
    //     $validated = $request->validate([
    //         'appointment_time' => 'nullable|date_format:Y-m-d H:i:s', // If you want to change the appointment time
    //         'status' => 'nullable|in:confirmed,cancelled,completed', // Three types of status: confirmed, cancelled, completed
    //     ]);

    //     // Find the appointment by its ID
    //     $appointment = Appointment::findOrFail($appointmentId);

    //     // Step 1: Check if the appointment time is being updated
    //     if (isset($validated['appointment_time'])) {
    //         // Convert the new appointment time to a Carbon instance
    //         $newAppointmentTime = Carbon::parse($validated['appointment_time']);

    //         // Check if the new slot is available
    //         $availability = ServiceProviderAvailability::where('provider_id', $appointment->provider_id)
    //             ->where('day', $newAppointmentTime->format('l')) // Get the day of the week (Monday, Tuesday, etc.)
    //             ->first();

    //         if ($availability) {
    //             // Check if the new time slot is available (not booked)
    //             $slot = AppointmentSlot::where('availability_id', $availability->id)
    //                 ->where('slot_time', $newAppointmentTime->format('H:i'))
    //                 ->first();

    //             if ($slot && !$slot->is_booked) {
    //                 // The slot is available, so update the appointment and mark the slot as booked
    //                 $appointment->update(['appointment_time' => $validated['appointment_time']]);

    //                 // Mark the new slot as booked
    //                 $slot->update(['is_booked' => true]);

    //                 // If the old slot is booked, mark it as available again (if applicable)
    //                 if ($appointment->status == 'confirmed') {
    //                     $oldSlot = AppointmentSlot::where('availability_id', $availability->id)
    //                         ->where('slot_time', Carbon::parse($appointment->appointment_time)->format('H:i'))
    //                         ->first();

    //                     if ($oldSlot && $oldSlot->is_booked) {
    //                         $oldSlot->update(['is_booked' => false]); // Mark the old slot as available again
    //                     }
    //                 }
    //             } else {
    //                 return response()->json(['error' => 'The selected time slot is not available.'], 400);
    //             }
    //         } else {
    //             return response()->json(['error' => 'Provider is not available at the new time.'], 400);
    //         }
    //     }

    //     // Step 2: Handle the status update
    //     if (isset($validated['status'])) {
    //         // Update the appointment status
    //         $appointment->update(['status' => $validated['status']]);

    //         // Handle logic based on the status change
    //         switch ($validated['status']) {
    //             case 'confirmed':
    //                 // Mark the slot as booked when the appointment is confirmed
    //                 $slot = AppointmentSlot::where('availability_id', $availability->id)
    //                     ->where('slot_time', Carbon::parse($appointment->appointment_time)->format('H:i'))
    //                     ->first();

    //                 if ($slot) {
    //                     $slot->update(['is_booked' => true]);
    //                 }
    //                 break;

    //             case 'cancelled':
    //                 // Mark the slot as available when the appointment is cancelled
    //                 $slot = AppointmentSlot::where('availability_id', $availability->id)
    //                     ->where('slot_time', Carbon::parse($appointment->appointment_time)->format('H:i'))
    //                     ->first();

    //                 if ($slot) {
    //                     $slot->update(['is_booked' => false]);
    //                 }
    //                 break;

    //             case 'completed':
                    
    //                 break;
    //         }
    //     }

    //     return response()->json(['message' => 'Appointment updated successfully', 'appointment' => $appointment]);
    // }

    public function deleteAppointment($appointmentId)
    {
        // Find the appointment by its ID
        $appointment = Appointment::findOrFail($appointmentId);

        // Check if the authenticated user is either the provider, the customer, or a super admin
        if ($appointment->customer_id !== auth()->user()->id && $appointment->provider_id !== auth()->user()->id && !auth()->user()->hasRole('super admin')) {
            return response()->json(['error' => 'Unauthorized to delete this appointment'], 403);
        }

        // Delete the appointment
        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted successfully']);
    }

    public function deleteAppointmentWithinTime($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

      

        // Check if the appointment is less than 24 hours from creating
        if (now()->diffInHours($appointment->appointment_time, false) > 24) {
            return response()->json([
                'error' => 'Appointment cannot be deleted now.'
            ], 403);
        }

        // Delete the appointment
        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted successfully']);
    }

    public function updateAppointment(Request $request, $appointmentId)
    {
        $validated = $request->validate([
            'appointment_time' => 'sometimes|nullable|date_format:Y-m-d H:i:s', // If you want to change the appointment time
            'status' => 'sometimes|nullable|in:confirmed,cancelled,completed', // Three types of status: confirmed, cancelled, completed
        ]);

        // Find the appointment by its ID
        $appointment = Appointment::findOrFail($appointmentId);

        // Step 1: Check if the appointment time is being updated
        if (isset($validated['appointment_time'])) {
            // Convert the new appointment time to a Carbon instance
            $newAppointmentTime = Carbon::parse($validated['appointment_time']);

            // Check if the new slot is available
            $availability = ServiceProviderAvailability::where('provider_id', $appointment->provider_id)
                // ->where('day', $newAppointmentTime->format('l')) // Get the day of the week (Monday, Tuesday, etc.)
            ->where('day', strtolower($newAppointmentTime->format('l')))

                ->first();

            if ($availability) {
                // Check if the new time slot is available (not booked)
                $slotAvailability = Appointment::where('provider_id', $appointment->provider_id)
                    ->where('appointment_time', Carbon::parse($validated['appointment_time']))
                    ->first();

                if (!$slotAvailability) {
                    // The slot is available, so update the appointment and mark the slot as booked
                    $appointment->update(['appointment_time' => $validated['appointment_time']]);   

                } else {
                    return response()->json(['error' => 'The selected time slot is not available.'], 400);
                }
            } else {
                return response()->json(['error' => 'Provider is not available at the new time.'], 400);
            }
        }

        // Step 2: Handle the status update
        if (isset($validated['status'])) {
            // Update the appointment status
            

            // Handle logic based on the status change
            switch ($validated['status']) {
                case 'confirmed':
                    $appointment->update(['status' => $validated['status']]);
                    break;

                case 'cancelled':
                    // Mark the slot as available when the appointment is cancelled
                    $appointment->delete();
                    break;

                case 'completed':
                    $appointment->update(['status' => $validated['status']]);
                    break;
            }
        }

        return response()->json(['message' => 'Appointment updated successfully', 'appointment' => $appointment]);
    }




    // public function checkAvailability(Request $request)
    // {
    //     // Validate input
    //     $validated = $request->validate([
    //         'provider_unique_user_id' => 'required|exists:users,unique_user_id',
    //         'appointment_date' => 'required|date_format:Y-m-d',
    //     ]);

    //     $provider = User::where('unique_user_id', $validated['provider_unique_user_id'])->first();

    //     if (!$provider) {
    //         return response()->json(['error' => 'Provider not found'], 404);
    //     }

    //     // Get the provider's availability for the selected date
    //     $availabilities = ServiceProviderAvailability::where('provider_id', $provider->id)
            
    //         ->where('day', strtolower(Carbon::parse($validated['appointment_date'])->format('l')))
    //         ->where('availability_type', 'appointment')

    //         ->get();

    //     if ($availabilities->isEmpty()) {
    //         return response()->json(['error' => 'Provider is not available on this date'], 400);
    //     }

    //     // Generate time slots for the available time range for each availability record
    //     $slotsWithStatus = [];

    //     foreach ($availabilities as $availability) {
    //         $timeSlots = $this->generateTimeSlots(
    //             $validated['appointment_date'],
    //             $availability->start_time,
    //             $availability->end_time,
    //             $availability->slot_duration
    //         );

    //         // Check the availability of each slot and mark it as booked or available
    //         foreach ($timeSlots as $slot) {
    //             $isBooked = Appointment::where('provider_id', $provider->id)
    //                 ->where('appointment_time', $slot)
    //                 ->exists();

    //             $slotsWithStatus[] = [
    //                 'slot' => $slot,
    //                 'is_booked' => $isBooked,
    //             ];
    //         }
    //     }

    //     return response()->json([
    //         'available_slots' => $slotsWithStatus
    //     ]);
    // }



    public function checkAvailability($provider_unique_user_id, $appointment_date)
    {
        // Validate input (provider_unique_user_id and appointment_date are now route parameters)
        $validated = [
            'provider_unique_user_id' => $provider_unique_user_id,
            'appointment_date' => $appointment_date,
        ];

        // Fetch provider by unique_user_id
        $provider = User::where('unique_user_id', $validated['provider_unique_user_id'])->first();

        if (!$provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }

        // Get the provider's availability for the selected date
        $availabilities = ServiceProviderAvailability::where('provider_id', $provider->id)
            ->where('day', strtolower(Carbon::parse($validated['appointment_date'])->format('l')))  // Get the day of the week
            ->where('availability_type', 'appointment')
            ->get();

        if ($availabilities->isEmpty()) {
            return response()->json(['error' => 'Provider is not available on this date'], 400);
        }

        // Generate time slots for the available time range for each availability record
        $slotsWithStatus = [];

        foreach ($availabilities as $availability) {
            $timeSlots = $this->generateTimeSlots(
                $validated['appointment_date'],
                $availability->start_time,
                $availability->end_time,
                $availability->slot_duration
            );

            // Check the availability of each slot and mark it as booked or available
            foreach ($timeSlots as $slot) {
                $isBooked = Appointment::where('provider_id', $provider->id)
                    ->where('appointment_time', $slot)
                    ->exists();

                $slotsWithStatus[] = [
                    'slot' => $slot,
                    'is_booked' => $isBooked,
                ];
            }
        }

        return response()->json([
            'available_slots' => $slotsWithStatus
        ]);
    }


    // Helper method to generate time slots based on provider's availability
    protected function generateTimeSlots($date, $startTime, $endTime, $slotDuration)
    {
        $start = Carbon::createFromFormat('Y-m-d H:i:s', "$date $startTime");
        $end = Carbon::createFromFormat('Y-m-d H:i:s', "$date $endTime");

        $slots = [];

        while ($start->lessThan($end)) {
            $slots[] = $start->format('Y-m-d H:i:s');
            $start->addMinutes($slotDuration); // Increment by the slot duration
        }

        return $slots;
    }
}