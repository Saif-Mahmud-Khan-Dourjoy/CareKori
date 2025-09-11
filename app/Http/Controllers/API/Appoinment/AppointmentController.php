<?php

namespace App\Http\Controllers\API\Appoinment;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Promocode;
use App\Models\ServiceProviderAvailability;
use App\Models\User;
use App\Notifications\AppointmentNotification;
use App\Notifications\CommonNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
    //         // ->where('day', Carbon::parse($validated['appointment_time'])->format('l'))
    //         ->where('day', strtolower(Carbon::parse($validated['appointment_time'])->format('l')))
    //         ->first();

    //     $slotAvailability = Appointment::where('provider_id', $provider->id)
    //         ->where('appointment_time', Carbon::parse($validated['appointment_time']))

    //         ->where('status', '!=', 'cancelled')
    //         ->first();

    //     if (!$availability || $slotAvailability) {
    //         return response()->json(['error' => 'Provider is not available on this time'], 400);
    //     }


    //     $prev_appointment = Appointment::where('customer_id', $customer->id)
    //         ->where('provider_id', $provider->id)
    //         ->where('appointment_time', Carbon::createFromFormat('Y-m-d H:i:s', $validated['appointment_time']))
    //         ->where('status',  'cancelled')
    //         ->first();

    //     if ($prev_appointment) {
    //         // Delete the cancelled appointment
    //         $prev_appointment->delete();
    //     }

    //     // Create the appointment
    //     $appointment = Appointment::create([
    //         'customer_id' => $customer->id,
    //         'provider_id' => $provider->id,
    //         'appointment_time' => Carbon::createFromFormat('Y-m-d H:i:s', $validated['appointment_time']),
    //         'status' => 'pending',
    //         'price' => $pricing,
    //     ]);



    //     // Deduct the balance from the customer’s wallet
    //     $customer->wallet->decrement('balance', $pricing);

    //     // $message = "A customer has booked for an appointment in " . $validated['appointment_time'];

    //     // $this->sendToPhone($provider->phone, $message);

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
            'isPromoApplied' => 'nullable|boolean',
            'promocode' => 'nullable|string|exists:promocodes,code'
        ]);

        // ✅ Parse and check if appointment time is in the past (in UTC)
        $appointmentTime = Carbon::createFromFormat('Y-m-d H:i:s', $validated['appointment_time'], 'UTC');
        $nowUtc = Carbon::now('UTC');

        if ($appointmentTime->lessThanOrEqualTo($nowUtc)) {
            return response()->json(['error' => 'You cannot book an appointment in the past.'], 422);
        }

        $user = User::findByUniqueUserId($validated['customer_unique_user_id']);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (auth()->user()->id !== $user->id) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        $customer = User::with('wallet')->where('unique_user_id', $validated['customer_unique_user_id'])->first();
        $provider = User::with(['role', 'doctorProfile', 'lawyerProfile', 'commonProfile'])
            ->where('unique_user_id', $validated['provider_unique_user_id'])->first();

        if (!$customer || !$provider) {
            return response()->json(['error' => 'Invalid customer or provider'], 400);
        }

       
        // "Switched" customers have the same phone as their provider + '5'
        $authUser = auth()->user();
        if ($authUser && Str::endsWith($authUser->phone ?? '', '5')) {
            $originalPhone = substr($authUser->phone, 0, -1);
            if (!empty($originalPhone)) {
                $originalProvider = User::where('phone', $originalPhone)->first();
                if ($originalProvider && (int)$originalProvider->id === (int)$provider->id) {
                    return response()->json([
                        'error' => 'You cannot book an appointment with your own provider account.'
                    ], 422);
                }
            }
        }

        $roleName = $provider->role->name ?? null;
        $profile = match ($roleName) {
            'doctor' => $provider->doctorProfile,
            'lawyer' => $provider->lawyerProfile,
            default => $provider->commonProfile
        };

        if (!$profile || is_null($profile->pricing)) {
            return response()->json(['error' => 'Provider pricing not found'], 400);
        }

        $originalPrice = $profile->pricing;
        $finalPrice = $originalPrice;

        if (!empty($validated['isPromoApplied']) && !empty($validated['promocode'])) {
            $promocode = Promocode::where('code', $validated['promocode'])
                ->where('is_active', true)
                ->whereDate('valid_from', '<=', now())
                ->whereDate('valid_to', '>=', now())
                ->first();

            if ($promocode) {
                $specialityId = match ($roleName) {
                    'doctor' => $profile->doctor_speciality_id,
                    'lawyer' => $profile->lawyer_speciality_id,
                    default => $profile->common_speciality_id,
                };
                $specialityType = $roleName;

                $isAssigned = $promocode->assignments()->where(function ($q) use ($provider, $specialityId, $specialityType) {
                    $q->where('user_id', $provider->id)
                        ->orWhere(fn($q) => $q->where('role_id', $provider->role_id)->whereNull('speciality_id'))
                        ->orWhere(fn($q) => $q->where('role_id', $provider->role_id)
                            ->where('speciality_id', $specialityId)
                            ->where('speciality_type', $specialityType));
                })->exists();

                if ($isAssigned) {
                    $discount = $promocode->discount_type === 'percent'
                        ? ($originalPrice * $promocode->discount / 100)
                        : min($promocode->discount, $originalPrice);
                    $finalPrice = $originalPrice - $discount;
                }
            }
        }

        if (($customer->wallet->balance ?? 0) < $finalPrice) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        $day = strtolower($appointmentTime->format('l'));
        $availabilities = ServiceProviderAvailability::where('provider_id', $provider->id)
            ->where('day', $day)
            ->get();

        $validSlot = false;

        foreach ($availabilities as $availability) {
            $start = Carbon::parse($appointmentTime->toDateString() . ' ' . $availability->start_time, 'UTC');
            $end   = Carbon::parse($appointmentTime->toDateString() . ' ' . $availability->end_time, 'UTC');

            if ($appointmentTime->between($start, $end)) {
                $validSlot = true;
                break;
            }
        }

        $slotTaken = Appointment::where('provider_id', $provider->id)
            ->where('appointment_time', $appointmentTime)
            ->where('status', '!=', 'cancelled')->exists();

        if (!$validSlot  || $slotTaken) {
            return response()->json(['error' => 'Provider is not available on this time'], 400);
        }

        Appointment::where('customer_id', $customer->id)
            ->where('provider_id', $provider->id)
            ->where('appointment_time', $appointmentTime)
            ->where('status', 'cancelled')->delete();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'provider_id' => $provider->id,
            'appointment_time' => $appointmentTime,
            'status' => 'confirmed',
            'price' => $finalPrice,
        ]);

        $customer->wallet->decrement('balance', $finalPrice);

       

        // $provider->notify(new AppointmentNotification($provider, $customer, $appointment, "booked"));
        // $customer->notify(new AppointmentNotification($customer, $provider, $appointment, "booked"));

        $provider->notify(new CommonNotification($provider,  "Appointment Booking", "{$customer->name} has booked an appointment with you at {$appointmentTime->toDateTimeString()} UTC"));
        $customer->notify(new CommonNotification($customer,  "Appointment Booking", "Your appointment with {$provider->name} has been successfully booked at {$appointmentTime->toDateTimeString()} UTC"));


        return response()->json([
            'message' => 'Appointment booked successfully',
            'appointment' => $appointment
        ]);
    }


    public function sendToPhone($phone, $messageToSend)
    {
        $token = env('BD_BULK_SMS_API_TOKEN');
        $to = $phone;
        $message = $messageToSend;

        $url = "https://api.bdbulksms.net/api.php?json";
        $data = [
            'to' => $to,
            'message' => $message,
            'token' => $token,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $smsResult = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return response()->json([
                'success' => false,
                'message' => 'cURL Error: ' . $curlError,
            ], 500);
        }

        $response = json_decode($smsResult, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON response',
                'raw_response' => $smsResult,
            ], 500);
        }

        $statusMessages = [];
        foreach ($response as $res) {
            $status = $res['status'] ?? 'UNKNOWN';
            $statusMsg = $res['statusmsg'] ?? 'No status message';
            $statusMessages[] = [
                'to' => $res['to'] ?? 'Unknown',
                'status' => $status,
                'message' => $statusMsg,
            ];
        }

        return $statusMessages;
    }

    public function getAppointmentsByUser($uniqueUserId)
    {

        $user = User::findByUniqueUserId($uniqueUserId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (auth()->user()->id !== $user->id) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        $appointments = Appointment::where('customer_id', $user->id)
            ->with('provider.role')
            ->orderBy('appointment_time', 'asc')
            ->get();



        foreach ($appointments as $appointment) {
            $provider = $appointment->provider;

            if (!$provider || !$provider->role) {
                continue;
            }

            $roleName = strtolower($provider->role->name);

            if ($roleName === 'doctor') {
                $provider->load([
                    'availability',
                    'doctorProfile',
                    'doctorProfile.doctorType',
                    'doctorProfile.doctorSpeciality',
                    'doctorProfile.doctorTitle'
                ]);
            } elseif ($roleName === 'lawyer') {
                $provider->load([
                    'availability',
                    'lawyerProfile',
                    'lawyerProfile.lawyerSpeciality',
                    'lawyerProfile.lawyerTitle'
                ]);
            } else {
                $provider->load([
                    'availability',
                    'commonProfile',
                    'commonProfile.uniqueIdentification',
                    'commonProfile.commonSpeciality'
                ]);
            }
        }

        return response()->json(['appointments' => $appointments]);
    }

    public function upcomingAppointmentsForUser($uniqueUserId)
    {

        $user = User::findByUniqueUserId($uniqueUserId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (auth()->user()->id !== $user->id) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }



        $appointments = Appointment::where('customer_id', $user->id)
            ->where('status', 'confirmed')
            ->where('appointment_time', '>=', now('UTC'))
            ->with('provider.role')
            ->orderBy('appointment_time', 'asc')
            ->get();


        foreach ($appointments as $appointment) {
            $provider = $appointment->provider;

            if (!$provider || !$provider->role) {
                continue;
            }

            $roleName = strtolower($provider->role->name);

            if ($roleName === 'doctor') {
                $provider->load([
                    'doctorProfile',
                    'doctorProfile.doctorType',
                    'doctorProfile.doctorSpeciality',
                    'doctorProfile.doctorTitle'
                ]);
            } elseif ($roleName === 'lawyer') {
                $provider->load([
                    'lawyerProfile',
                    'lawyerProfile.lawyerSpeciality',
                    'lawyerProfile.lawyerTitle'
                ]);
            } else {
                $provider->load([
                    'commonProfile',
                    'commonProfile.uniqueIdentification',
                    'commonProfile.commonSpeciality'
                ]);
            }
        }

        // Fix: apply metrics to actual provider object
        $appointments->each(function ($appointment) {
            $provider = $appointment->provider;

            if ($provider) {
                $completedAppointments = Appointment::where('provider_id', $provider->id)
                    ->whereRaw('LOWER(status) LIKE ?', ['%complete%'])
                    // ->distinct('customer_id')
                    ->count('customer_id');

                $provider->customers_count = $completedAppointments;
                $provider->average_rating = $provider->averageRating();
                $provider->review_count = $provider->reviewCount();
                if ($provider->average_rating == 0) {
                    $provider->average_rating == 0.0;
                }
                $provider->experience_count = rand(0, 9); // Replace with real logic if needed
            }
        });

        return response()->json(['appointments' => $appointments]);
    }



    public function historyAppointmentsForUser($uniqueUserId)
    {


        $user = User::findByUniqueUserId($uniqueUserId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (auth()->user()->id !== $user->id) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }


        $appointments = Appointment::where('customer_id', $user->id)
            ->whereIn('status', ['confirmed', 'completed', 'cancelled', 'pending'])
            ->where('appointment_time', '<', now('UTC'))
            ->with('provider.role')
            ->orderBy('appointment_time', 'asc')
            ->get();


        foreach ($appointments as $appointment) {
            $provider = $appointment->provider;

            if (!$provider || !$provider->role) {
                continue;
            }

            $roleName = strtolower($provider->role->name);

            if ($roleName === 'doctor') {
                $provider->load([
                    'doctorProfile',
                    'doctorProfile.doctorType',
                    'doctorProfile.doctorSpeciality',
                    'doctorProfile.doctorTitle'
                ]);
            } elseif ($roleName === 'lawyer') {
                $provider->load([
                    'lawyerProfile',
                    'lawyerProfile.lawyerSpeciality',
                    'lawyerProfile.lawyerTitle'
                ]);
            } else {
                $provider->load([
                    'commonProfile',
                    'commonProfile.uniqueIdentification',
                    'commonProfile.commonSpeciality'
                ]);
            }
        }

        $appointments->each(function ($appointment) {
            $provider = $appointment->provider;

            if ($provider) {
                $completedAppointments = Appointment::where('provider_id', $provider->id)
                    ->whereRaw('LOWER(status) LIKE ?', ['%complete%'])
                    // ->distinct('customer_id')
                    ->count('customer_id');

                $provider->customers_count = $completedAppointments;
                $provider->average_rating = $provider->averageRating();
                $provider->review_count = $provider->reviewCount();
                $provider->experience_count = rand(0, 9); // Replace with real logic if needed
            }
        });

        return response()->json(['appointments' => $appointments]);
    }


    public function getAppointmentsByProvider($uniqueUserId)
    {

        $provider = User::findByUniqueUserId($uniqueUserId);

        if (!$provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }

        if (auth()->user()->id !== $provider->id) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        $appointments = Appointment::with('customer.customerProfile')->where('provider_id', $provider->id)->get();

        return response()->json(['appointments' => $appointments]);
    }

    public function upcomingAppointmentsForProvider($uniqueUserId)
    {

        $provider = User::findByUniqueUserId($uniqueUserId);

        if (!$provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }

        if (auth()->user()->id !== $provider->id) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }


        $appointments = Appointment::where('provider_id', $provider->id)
            ->where('status', 'confirmed')
            ->where('appointment_time', '>=', now('UTC'))
            ->with('customer.customerProfile')
            ->orderBy('appointment_time', 'asc')
            ->get();


        // $appointments->each(function ($appointment) {
        //     $providerId = $appointment->provider_id;

        //     $provider = User::findOrFail($providerId);

        //     if ($provider) {
        //         $completedAppointments = Appointment::where('provider_id', $provider->id)
        //             ->whereRaw('LOWER(status) LIKE ?', ['%complete%'])
        //             ->distinct('customer_id')
        //             ->count('customer_id');

        //         $appointment->customers_count = $completedAppointments;
        //         $appointment->average_rating = $provider->averageRating();
        //         $appointment->review_count = $provider->reviewCount();
        //         $appointment->experience_count = rand(0, 9); // Replace with real logic if needed
        //     }
        // });

        $providerInfo = [];

        $completedAppointments = Appointment::where('provider_id', $provider->id)
            ->whereRaw('LOWER(status) LIKE ?', ['%complete%'])
            // ->distinct('customer_id')
            ->count('customer_id');

        $providerInfo['customers_count'] = $completedAppointments;
        $providerInfo['average_rating'] = $provider->averageRating();
        $providerInfo['review_count'] = $provider->reviewCount();
        $providerInfo['experience_count'] = rand(0, 9);
        $providerInfo['average_rating'] = (float) ($providerInfo['average_rating'] ?? 0.0);







        return response()->json(['appointments' => $appointments, 'providerInfo' => $providerInfo]);
    }

    public function historyAppointmentsForProvider($uniqueUserId)
    {

        $provider = User::findByUniqueUserId($uniqueUserId);

        if (!$provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }

        if (auth()->user()->id !== $provider->id) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }


        $appointments = Appointment::where('provider_id', $provider->id)
            ->whereIn('status', ['confirmed', 'completed', 'cancelled', 'pending'])
            ->where('appointment_time', '<', now('UTC'))
            ->with('customer.customerProfile')
            ->orderBy('appointment_time', 'asc')
            ->get();


        // $appointments->each(function ($appointment) {
        //     $providerId = $appointment->provider_id;

        //     $provider = User::findOrFail($providerId);

        //     if ($provider) {
        //         $completedAppointments = Appointment::where('provider_id', $provider->id)
        //             ->whereRaw('LOWER(status) LIKE ?', ['%complete%'])
        //             ->distinct('customer_id')
        //             ->count('customer_id');

        //         $appointment->customers_count = $completedAppointments;
        //         $appointment->average_rating = $provider->averageRating();
        //         $appointment->review_count = $provider->reviewCount();
        //         $appointment->experience_count = rand(0, 9); // Replace with real logic if needed
        //     }
        // });

        $providerInfo = [];

        $completedAppointments = Appointment::where('provider_id', $provider->id)
            ->whereRaw('LOWER(status) LIKE ?', ['%complete%'])
            // ->distinct('customer_id')
            ->count('customer_id');

        $providerInfo['customers_count'] = $completedAppointments;
        $providerInfo['average_rating'] = $provider->averageRating();
        $providerInfo['review_count'] = $provider->reviewCount();
        $providerInfo['experience_count'] = rand(0, 9);






        return response()->json(['appointments' => $appointments, 'providerInfo' => $providerInfo]);

        // return response()->json(['appointments' => $appointments]);
    }

    /**
     * Update an appointment by its ID.
     *
     * @param Request $request
     * @param int $appointmentId
     * @return \Illuminate\Http\JsonResponse
     */

    // public function updateAppointment(Request $request, $appointmentId)
    // {
    //     $validated = $request->validate([
    //         'appointment_time' => 'nullable|date_format:Y-m-d H:i:s', // If you want to change the appointment time
    //         'status' => 'nullable|in:confirmed,cancelled,completed', // Three types of status: confirmed, cancelled, completed
    //     ]);

    //     
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


    /**
     * Delete an appointment by its ID.
     *
     * @param int $appointmentId
     * @return \Illuminate\Http\JsonResponse
     */
    // public function deleteAppointment($appointmentId)
    // {
    //     
    //     $appointment = Appointment::findOrFail($appointmentId);

    //     // Check if the authenticated user is either the provider, the customer, or a super admin
    //     // if ($appointment->customer_id !== auth()->user()->id && $appointment->provider_id !== auth()->user()->id && !auth()->user()->hasRole('super admin')) {
    //     //     return response()->json(['error' => 'Unauthorized to delete this appointment'], 403);
    //     // }

    //     // Delete the appointment
    //     $appointment->delete();

    //     return response()->json(['message' => 'Appointment deleted successfully']);
    // }

    public function deleteAppointment($appointmentId)
    {

        $appointment = Appointment::findOrFail($appointmentId);

        $appointmentTimeUTC = Carbon::parse($appointment->appointment_time, 'UTC');

        if (!$appointment->is_money_back) {

            $wallet = $appointment->customer->wallet;
            if ($wallet) {
                $wallet->increment('balance', $appointment->price);
            }

            // // Update is_money_back to true in the appointment table
            // $appointment->is_money_back = true;
            // $appointment->save();  // Save the change
        }


        $appointment->delete();

        $appointment->provider->notify(new CommonNotification($appointment->provider,  "Appointment Deletion", "Your appointment with {$appointment->customer->name} at {$appointmentTimeUTC->format('Y-m-d H:i:s')} UTC has been deleted"));
        $appointment->customer->notify(new CommonNotification($appointment->customer,  "Appointment Deletion", "Your appointment with {$appointment->provider->name} at {$appointmentTimeUTC->format('Y-m-d H:i:s')} UTC has been deleted"));


        return response()->json(['message' => 'Appointment deleted successfully']);
    }



    // public function cancelAppointmentWithinTime($appointmentId)
    // {
    //     $appointment = Appointment::findOrFail($appointmentId);

    //     // Check if the appointment is less than 24 hours away
    //     if (now()->diffInHours($appointment->appointment_time, false) < 24) {
    //         return response()->json([
    //             'error' => 'Appointment cannot be cancelled now.'
    //         ], 403);
    //     }

    //     // Update the status to 'cancelled'
    //     $appointment->status = 'cancelled';
    //     $appointment->save();

    //     return response()->json(['message' => 'Appointment cancelled successfully']);
    // }

    public function cancelAppointmentWithinTime(Request $request, $appointmentId)
    {




        $validated = $request->validate([
            'remarks' => 'nullable|string|max:500', // Optional remarks field
        ]);
        $appointment = Appointment::with(['customer', 'customer.wallet', 'provider'])->find($appointmentId);

        if (!$appointment) {
            return response()->json(['error' => 'Appointment not found'], 404);
        }

        if ($appointment->customer_id != auth()->user()->id) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        if (!$appointment) {
            return response()->json(['error' => 'Appointment not found'], 404);
        }


        $appointmentTimeUTC = Carbon::parse($appointment->appointment_time, 'UTC');




        if (now('UTC')->diffInHours($appointmentTimeUTC, false) < 24) {
            return response()->json([
                'error' => 'Appointment cannot be cancelled now.'
            ], 403);
        }


        if ($appointment->status === 'cancelled') {
            return response()->json([
                'error' => 'Appointment is already cancelled.'
            ], 400);
        }

        $remarks = $validated['remarks'] ?? null;


        $wallet = $appointment->customer->wallet;
        if ($wallet) {
            $wallet->increment('balance', $appointment->price);
        }


        $appointment->update([
            'status' => 'cancelled',
            'is_money_back' => true,
            'is_cancel_by_user' => true,
            'remarks' => $remarks,
        ]);

        // $appointment->provider->notify(new AppointmentNotification($appointment->provider, $appointment->customer, $appointment, "Cancelled"));
        // $appointment->customer->notify(new AppointmentNotification($appointment->customer, $appointment->provider, $appointment, "Cancelled"));

        $appointment->provider->notify(new CommonNotification($appointment->provider,  "Appointment Canceling", "{$appointment->customer->name} has cancelled their appointment with you at {$appointmentTimeUTC->format('Y-m-d H:i:s')} UTC"));
        $appointment->customer->notify(new CommonNotification($appointment->customer,  "Appointment Canceling", "Your appointment with {$appointment->provider->name} at {$appointmentTimeUTC->format('Y-m-d H:i:s')} UTC has been cancelled"));



        return response()->json([
            'message' => 'Appointment cancelled and money refunded successfully.'
        ]);
    }



    public function updateAppointment(Request $request, $appointmentId)
    {
        
        $validated = $request->validate([
            'appointment_time' => 'sometimes|nullable|date_format:Y-m-d H:i:s',
            'status' => 'sometimes|nullable|in:confirmed,cancelled,completed',
        ]);

        $appointment = Appointment::findOrFail($appointmentId);

        if ($appointment->customer_id != auth()->user()->id) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        // Only allow updates to confirmed appointments
        if ($appointment->status !== 'confirmed') {
            return response()->json(['error' => 'Only confirmed appointments can be updated'], 400);
        }

        // Update appointment time
        if (!empty($validated['appointment_time'])) {
            // ✅ Parse in UTC
            $newTime = Carbon::createFromFormat('Y-m-d H:i:s', $validated['appointment_time'], 'UTC');
            $nowUtc = Carbon::now('UTC');

            // ✅ Ensure new appointment time is in the future (in UTC)
            if ($newTime->lessThanOrEqualTo($nowUtc)) {
                return response()->json(['error' => 'Appointment time must be in the future'], 400);
            }

            $day = strtolower($newTime->format('l'));

            // $availability = ServiceProviderAvailability::where('provider_id', $appointment->provider_id)
            //     ->where('day', $day)
            //     ->first();

           

            $availabilities = ServiceProviderAvailability::where('provider_id', $appointment->provider_id)
                ->where('day', $day)
                ->get();

            $validSlot = false;

            foreach ($availabilities as $availability) {
                $start = Carbon::parse($newTime->toDateString() . ' ' . $availability->start_time, 'UTC');
                $end   = Carbon::parse($newTime->toDateString() . ' ' . $availability->end_time, 'UTC');

                if ($newTime->between($start, $end)) {
                    $validSlot = true;
                    break;
                }
            }

            if (!$validSlot) {
                return response()->json(['error' => 'Provider is not available on this day'], 400);
            }

            // ✅ Check if slot is already taken by another appointment
            $slotTaken = Appointment::where('provider_id', $appointment->provider_id)
                ->where('appointment_time', $newTime)
                ->where('status', '!=', 'cancelled')
                ->where('id', '!=', $appointment->id)
                ->exists();

            if ($slotTaken) {
                return response()->json(['error' => 'The selected time slot is already taken'], 400);
            }

            // ✅ Save UTC time
            $appointment->appointment_time = $newTime;
        }

        // Update status if provided
        if (!empty($validated['status'])) {
            switch ($validated['status']) {
                case 'confirmed':
                case 'completed':
                    $appointment->status = $validated['status'];
                    break;

                case 'cancelled':
                    $appointment->status = $validated['status'];
                    break;
            }
        }

        $appointment->update();

        $app_time= Carbon::parse($appointment->appointment_time,'UTC')->format('Y-m-d H:i:s');

        $appointment->provider->notify(new CommonNotification($appointment->provider,  "Appointment Update", "Your appointment with {$appointment->customer->name} has been updated, time: {$app_time} UTC and status: {$appointment->status}"));
        $appointment->customer->notify(new CommonNotification($appointment->customer,  "Appointment Update", "Your appointment with {$appointment->provider->name} has been updated, time: {$app_time} UTC and status: {$appointment->status}"));

        return response()->json([
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment
        ]);
    }



    /**
     * Update the status of an appointment by its ID.
     *
     * @param Request $request
     * @param int $appointmentId
     * @return \Illuminate\Http\JsonResponse
     */

    // public function updateAppointmentStatus(Request $request, $appointmentId)
    // {
    //     $validated = $request->validate([

    //         'status' => 'required|in:pending,confirmed,cancelled,completed', // Three types of status: pending,confirmed, cancelled, completed
    //     ]);


    //     
    //     $appointment = Appointment::findOrFail($appointmentId);

    //     $customer = $appointment->customer;

    //     // Step 1: Check if the appointment time is being updated


    //     // Step 2: Handle the status update
    //     if (isset($validated['status'])) {
    //         // Update the appointment status


    //         // Handle logic based on the status change
    //         $appointment->update(['status' => $validated['status']]);
    //         // $message = 'Your appointment status has been updated to ' . $validated['status'] . '.';
    //         // $this->sendToPhone($customer->phone, $message);
    //     }

    //     return response()->json(['message' => 'Appointment Status updated successfully', 'appointment' => $appointment]);
    // }

    public function updateAppointmentStatus(Request $request, $appointmentId)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);


        $appointment = Appointment::findOrFail($appointmentId);

        if (!$appointment) {
            return response()->json(['error' => 'Appointment not found'], 404);
        }


        if ($appointment->is_cancel_by_user) {
            return response()->json([
                'message' => 'This appointment was cancelled by the user. No action can be performed.'
            ], 403);
        }


        if ($validated['status'] === 'cancelled') {


            if (!$appointment->is_money_back) {

                $wallet = $appointment->customer->wallet;
                if ($wallet) {
                    $wallet->increment('balance', $appointment->price);
                }


                $appointment->is_money_back = true;
                $appointment->save();
            }

            $appointment->update(['status' => 'cancelled']);
        } else {

            $appointment->update(['status' => $validated['status']]);
        }

        $app_time = Carbon::parse($appointment->appointment_time, 'UTC')->format('Y-m-d H:i:s');

        $appointment->provider->notify(new CommonNotification($appointment->provider,  "Appointment Update", "Your appointment with {$appointment->customer->name} has been updated, time: {$app_time} UTC and status: {$appointment->status}"));
        $appointment->customer->notify(new CommonNotification($appointment->customer,  "Appointment Update", "Your appointment with {$appointment->provider->name} has been updated, time: {$app_time} UTC and status: {$appointment->status}"));




        return response()->json([
            'message' => 'Appointment Status updated successfully',
            'appointment' => $appointment
        ]);
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



    // public function checkAvailability($provider_unique_user_id, $appointment_date)
    // {

    //     $validated = [
    //         'provider_unique_user_id' => $provider_unique_user_id,
    //         'appointment_date' => $appointment_date,
    //     ];


    //     $provider = User::where('unique_user_id', $validated['provider_unique_user_id'])->first();

    //     if (!$provider) {
    //         return response()->json(['error' => 'Provider not found'], 404);
    //     }


    //     $availabilities = ServiceProviderAvailability::where('provider_id', $provider->id)
    //         ->where('day', strtolower(Carbon::parse($validated['appointment_date'])->format('l')))
    //         ->where('availability_type', 'appointment')
    //         ->get();

    //     if ($availabilities->isEmpty()) {
    //         return response()->json(['error' => 'Provider is not available on this date'], 400);
    //     }

    //     $slotsWithStatus = [];

    //     foreach ($availabilities as $availability) {
    //         $timeSlots = $this->generateTimeSlots(
    //             $validated['appointment_date'],
    //             $availability->start_time,
    //             $availability->end_time,
    //             $availability->slot_duration
    //         );


    //         foreach ($timeSlots as $slot) {
    //             $isBooked = Appointment::where('provider_id', $provider->id)
    //                 ->where('appointment_time', $slot)
    //                 ->where('status', '!=', 'cancelled')
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
        try {
            $validated = [
                'provider_unique_user_id' => $provider_unique_user_id,
                'appointment_date' => $appointment_date,
            ];

            $provider = User::where('unique_user_id', $validated['provider_unique_user_id'])->first();

            if (!$provider) {
                return response()->json(['error' => 'Provider not found'], 404);
            }

            $availabilities = ServiceProviderAvailability::where('provider_id', $provider->id)
                ->where('day', strtolower(Carbon::parse($validated['appointment_date'])->format('l')))
                ->where('availability_type', 'appointment')
                ->get();

            if ($availabilities->isEmpty()) {
                return response()->json(['error' => 'Provider is not available on this date'], 400);
            }

            $slotsWithStatus = [];

            foreach ($availabilities as $availability) {

                $timeSlots = $this->generateTimeSlots(
                    $validated['appointment_date'],
                    $availability->start_time,
                    $availability->end_time,
                    env('SLOT_DURATION', 60) // Default to 60 if not set
                );

                foreach ($timeSlots as $slot) {
                    $isBooked = Appointment::where('provider_id', $provider->id)
                        ->where('appointment_time', $slot)
                        ->where('status', '!=', 'cancelled')
                        ->exists();

                    $slotUtc = Carbon::parse($slot, env('CUSTOMER_TIMEZONE', 'UTC'))->toISOString();

                    $slotsWithStatus[] = [
                        'slot' => $slotUtc,
                        'is_booked' => $isBooked,
                    ];
                }
            }

            return response()->json([
                'slot_duration' => env('SLOT_DURATION', 60),
                'available_slots' => $slotsWithStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }









    // Helper method to generate time slots based on provider's availability
    protected function generateTimeSlots($date, $startTime, $endTime, $slotDuration)
    {
        $start = Carbon::createFromFormat('Y-m-d H:i:s', "$date $startTime");
        $end = Carbon::createFromFormat('Y-m-d H:i:s', "$date $endTime");

        $slots = [];

        while ($start->lessThan($end)) {
            $slots[] = $start->format('Y-m-d H:i:s');
            $start->addMinutes($slotDuration);
        }

        return $slots;
    }

    public function getAppointmentsByDate($date)
    {

        $validatedDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();

        if (!$validatedDate) {
            return response()->json(['error' => 'Invalid date format'], 400);
        }




        $providerId = Auth::id();


        $appointments = Appointment::where('provider_id', $providerId)
            ->whereDate('appointment_time', '=', $validatedDate)  // Filter by date only (ignores time)
            ->with(['customer', 'customer.customerProfile'])
            ->get();

        // Return the appointments data in the response
        return response()->json([
            'appointments' => $appointments
        ]);
    }



    public function appointmentsWithCustomer($customerUniqueId)
    {

        $provider = Auth::user();
        if (!$provider) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }


        $customer = User::findByUniqueUserId($customerUniqueId);
        if (!$customer) {
            return response()->json(['error' => 'Customer not found'], 404);
        }


        $appointments = Appointment::where('appointments.provider_id', $provider->id)
            ->where('appointments.customer_id', $customer->id)
            ->join('users', 'users.id', '=', 'appointments.customer_id')
            ->join('customer_profiles', 'customer_profiles.user_id', '=', 'appointments.customer_id')
            ->orderBy('appointments.appointment_time', 'desc')
            ->select([
                'appointments.*',
                'users.name as customer_name',
                'customer_profiles.gender',
                'customer_profiles.dob',
                'customer_profiles.address',
                'customer_profiles.avatar',
            ])
            ->get();


        return response()->json([
            'count' => $appointments->count(),
            'appointments' => $appointments,
        ]);
    }


    public function providerOverview()
    {
        $provider = Auth::user();

        if (!$provider) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $now = Carbon::now('UTC'); // all comparison in UTC

        // Upcoming: confirmed and appointment_time is in the future
        $upcoming = Appointment::where('provider_id', $provider->id)
            ->where('status', 'confirmed')
            ->where('appointment_time', '>', $now)
            ->orderBy('appointment_time')
            ->get();

        // Completed
        $completed = Appointment::where('provider_id', $provider->id)
            ->where('status', 'completed')
            ->orderBy('appointment_time', 'desc')
            ->get();

        // Cancelled
        $cancelled = Appointment::where('provider_id', $provider->id)
            ->where('status', 'cancelled')
            ->orderBy('appointment_time', 'desc')
            ->get();

        return response()->json([
            'upcoming_count' => $upcoming->count(),
            'upcoming_appointments' => $upcoming,

            'completed_count' => $completed->count(),
            'completed_appointments' => $completed,

            'cancelled_count' => $cancelled->count(),
            'cancelled_appointments' => $cancelled,
        ]);
    }


    public function cancelAppointmentByProvider($id)
    {
        $provider = Auth::user();

        if (!$provider) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $appointment = Appointment::where('id', $id)
            ->where('provider_id', $provider->id)
            ->first();

        if (!$appointment) {
            return response()->json(['error' => 'Appointment not found or unauthorized'], 404);
        }

        if (in_array($appointment->status, ['cancelled', 'completed'])) {
            return response()->json(['error' => 'Appointment cannot be cancelled.'], 400);
        }

        if (!$appointment->is_money_back) {

            $wallet = $appointment->customer->wallet;
            if ($wallet) {
                $wallet->increment('balance', $appointment->price);
            }


            $appointment->is_money_back = true;
        }


        $appointment->status = 'cancelled';
        $appointment->save();

        $appointment->customer->makeHidden('wallet');

        $app_time = Carbon::parse($appointment->appointment_time, 'UTC')->format('Y-m-d H:i:s');

        $appointment->provider->notify(new CommonNotification($appointment->provider,  "Appointment Cancelation", "Your appointment with {$appointment->customer->name} at {$app_time} UTC has been cancelled."));
        $appointment->customer->notify(new CommonNotification($appointment->customer,  "Appointment Cancelation", "Your appointment with {$appointment->provider->name} at {$app_time} UTC has been cancelled."));


        return response()->json([
            'message' => 'Appointment cancelled successfully and also refunded.',
            'appointment' => $appointment
        ]);
    }


    // public function rescheduleAppointment(Request $request, $id)
    // {
    //     $request->validate([
    //         'new_time' => 'required|date|after:now|date_format:Y-m-d H:i:s',
    //     ]);

    //     $provider = Auth::user();

    //     // Parse and convert to UTC
    //     $newTime = Carbon::createFromFormat('Y-m-d H:i:s', $request->new_time, 'UTC');

    //     $appointment = Appointment::where('id', $id)
    //         ->where('provider_id', $provider->id)
    //         ->first();

    //     if (!$appointment) {
    //         return response()->json(['error' => 'Appointment not found'], 404);
    //     }

    //     if ($appointment->status !== 'confirmed') {
    //         return response()->json(['error' => 'Only confirmed appointments can be rescheduled.'], 400);
    //     }

    //     $customer = $appointment->customer;

    //     // Availability check
    //     $day = strtolower($newTime->format('l'));
    //     $time = $newTime->format('H:i:s');

    //     $availability = ServiceProviderAvailability::where('provider_id', $provider->id)
    //         ->where('availability_type', 'appointment')
    //         ->where('day', $day)
    //         ->where('start_time', '<=', $time)
    //         ->where('end_time', '>', $time)
    //         ->first();

    //     if (!$availability) {
    //         return response()->json(['error' => 'The selected time is not within provider availability.'], 400);
    //     }

    //     // Slot check
    //     $slotTaken = Appointment::where('provider_id', $provider->id)
    //         ->where('appointment_time', $newTime)
    //         ->where('status', '!=', 'cancelled')
    //         ->exists();

    //     if ($slotTaken) {
    //         return response()->json(['error' => 'This time slot is already taken.'], 400);
    //     }

    //     // Delete old cancelled
    //     Appointment::where('customer_id', $customer->id)
    //         ->where('provider_id', $provider->id)
    //         ->where('appointment_time', $newTime)
    //         ->where('status', 'cancelled')
    //         ->delete();

    //     // Save new appointment time
    //     $appointment->appointment_time = $newTime;
    //     $appointment->save();

    //     return response()->json([
    //         'message' => 'Appointment rescheduled successfully.',
    //         'appointment' => $appointment
    //     ]);
    // }

    public function rescheduleAppointment(Request $request, $id)
    {
        $request->validate([
            'new_time' => 'required|date|after:now|date_format:Y-m-d H:i:s',
        ]);

        $user = Auth::user();

        // Parse and convert to UTC
        $newTime = Carbon::createFromFormat('Y-m-d H:i:s', $request->new_time, 'UTC');

        // Find appointment where user is either provider or customer
        $appointment = Appointment::where('id', $id)
            ->where(function ($query) use ($user) {
                $query->where('provider_id', $user->id)
                    ->orWhere('customer_id', $user->id);
            })
            ->first();

        if (!$appointment) {
            return response()->json(['error' => 'Appointment not found or unauthorized'], 404);
        }

        if ($appointment->status !== 'confirmed') {
            return response()->json(['error' => 'Only confirmed appointments can be rescheduled.'], 400);
        }

        $providerId = $appointment->provider_id;
        $customerId = $appointment->customer_id;

        // ✅ Provider availability check
        $day = strtolower($newTime->format('l'));
        $time = $newTime->format('H:i:s');

        $availability = ServiceProviderAvailability::where('provider_id', $providerId)
            ->where('availability_type', 'appointment')
            ->where('day', $day)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->first();

        if (!$availability) {
            return response()->json(['error' => 'The selected time is not within provider availability.'], 400);
        }

        // ✅ Slot check
        $slotTaken = Appointment::where('provider_id', $providerId)
            ->where('appointment_time', $newTime)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($slotTaken) {
            return response()->json(['error' => 'This time slot is already taken.'], 400);
        }

        // ✅ Delete previously cancelled appointment in same slot
        Appointment::where('customer_id', $customerId)
            ->where('provider_id', $providerId)
            ->where('appointment_time', $newTime)
            ->where('status', 'cancelled')
            ->delete();

        // ✅ Reschedule
        $appointment->appointment_time = $newTime;
        $appointment->save();

      

        $appointment->provider->notify(new CommonNotification($appointment->provider,  "Appointment Re-Schedule", "Your appointment with {$appointment->customer->name}  has been rescheduled at {$newTime->format('Y-m-d H:i:s')} UTC."));
        $appointment->customer->notify(new CommonNotification($appointment->customer,  "Appointment Re-Schedule", "Your appointment with {$appointment->provider->name} at {$newTime->format('Y-m-d H:i:s')} UTC has been rescheduled."));

        return response()->json([
            'message' => 'Appointment rescheduled successfully.',
            'appointment' => $appointment
        ]);
    }
}
