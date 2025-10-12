<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CommonProfile;
use App\Models\CommonProviderSpeciality;
use App\Models\DoctorSpeciality;
use App\Models\LawyerSpeciality;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ServiceProvider extends Controller
{
    public function getServiceProvider()
    {
        $serviceProvider = Role::whereNotIn('name', ['super admin', 'moderator', 'customer'])->withCount('users')->get();

        return response()->json([
            'data' => $serviceProvider,
            'message' => 'Service Provider fetched successfully',
            'status' => true,
            'code' => 200
        ], 200);
    }

    // public function serviceProviderSpeciality($roleId)
    // {
    //     $serviceProvider = Role::find($roleId);
    //     if (!$serviceProvider) {
    //         return response()->json([
    //             'message' => 'Service Provider not found',
    //             'status' => false,
    //             'code' => 404
    //         ], 404);
    //     }
    //     switch ($serviceProvider->name) {
    //         case 'doctor':
    //             $speciality = DoctorSpeciality::all();
    //             break;
    //         case 'lawyer':
    //             $speciality = LawyerSpeciality::all();
    //             break;
    //         default:
    //             $speciality = CommonProviderSpeciality::where('category_id', $roleId)->get();
    //             break;
    //     }

    //     return response()->json([
    //         'data' => $speciality,
    //         'roleId' => $roleId,
    //         'message' => 'Service Provider speciality fetched successfully',
    //         'status' => true,
    //         'code' => 200
    //     ], 200);
    // }



    public function serviceProviderSpeciality($roleId)
    {
        $serviceProvider = Role::find($roleId);

        if (!$serviceProvider) {
            return response()->json([
                'message' => 'Service Provider not found',
                'status' => false,
                'code' => 404
            ], 404);
        }

        switch ($serviceProvider->name) {
            case 'doctor':
                // Fetch specialities with user count
                $speciality = DoctorSpeciality::withCount(['doctorProfiles as users_count' => function ($query) use ($roleId) {
                    $query->whereHas('user', function ($q) use ($roleId) {
                        $q->where('role_id', $roleId);
                    });
                }])->get();
                break;

            case 'lawyer':
                $speciality = LawyerSpeciality::withCount(['lawyerProfiles as users_count' => function ($query) use ($roleId) {
                    $query->whereHas('user', function ($q) use ($roleId) {
                        $q->where('role_id', $roleId);
                    });
                }])->get();
                break;

            default:
                $speciality = CommonProviderSpeciality::withCount(['commonProfiles as users_count' => function ($query) use ($roleId) {
                    $query->whereHas('user', function ($q) use ($roleId) {
                        $q->where('role_id', $roleId);
                    });
                }])
                    ->where('category_id', $roleId)
                    ->get();
                break;
        }

        return response()->json([
            'data' => $speciality,
            'roleId' => $roleId,
            'message' => 'Service Provider speciality fetched successfully',
            'status' => true,
            'code' => 200
        ], 200);
    }


    // public function serviceProviderListBySpeciality($specialityId, $roleId)
    // {
    //     $role = Role::find($roleId);
    //     switch (Str::lower($role->name)) {
    //         case 'doctor':
    //             $serviceProvider = User::with(['doctorProfile', 'doctorProfile.doctorType', 'doctorProfile.doctorSpeciality', 'doctorProfile.doctorTitle', 'availability'=>function($query){ $query->select('day', 'provider_id','start_time','end_time','slot_duration')->where('availability_type', 'appointment');}])->where('role_id', $roleId)->whereHas('doctorProfile', function ($query) use ($specialityId) {
    //                 $query->where('doctor_speciality_id', $specialityId);
    //             })->get();
    //             break;
    //         case 'lawyer':
    //             $serviceProvider = User::with(['lawyerProfile', 'lawyerProfile.lawyerTitle', 'lawyerProfile.lawyerSpeciality', 'availability' => function ($query) {
    //                 $query->select('day', 'provider_id', 'start_time', 'end_time', 'slot_duration')->where('availability_type', 'appointment');
    //             }])->where('role_id', $roleId)->whereHas('lawyerProfile', function ($query) use ($specialityId) {
    //                 $query->where('lawyer_speciality_id', $specialityId);
    //             })->get();
    //             break;
    //         default:
    //             $serviceProvider = User::where('role_id', $roleId)
    //                 ->whereHas('commonProfile', function ($query) use ($specialityId) {
    //                     $query->where('common_speciality_id', $specialityId);
    //                 })
    //                 ->with(['commonProfile', 'commonProfile.uniqueIdentification', 'commonProfile.commonSpeciality', 'availability' => function ($query) {
    //                 $query->select('day', 'provider_id', 'start_time', 'end_time', 'slot_duration')->where('availability_type', 'appointment');
    //             }]) // Eager load related data
    //                 ->get();
    //             break;
    //     }
    //     if ($serviceProvider->isEmpty()) {
    //         return response()->json([
    //             'message' => 'No service provider found for this speciality',
    //             'status' => false,
    //             'code' => 404
    //         ], 404);
    //     }

    //     // Adding the unique customer count for each service provider (with completed appointments)
    //     $serviceProvider->each(function ($provider) {
    //         $completedAppointments = Appointment::where('provider_id', $provider->id)
    //             // ->where('status', 'completed')
    //             ->whereRaw('LOWER(status) LIKE ?', ['%complete%'])
    //             ->distinct('customer_id')
    //             ->count('customer_id');
                

    //         // Add the count to the provider's object
    //         $provider->customers_count = $completedAppointments;
    //         $provider->average_rating = $provider->averageRating();
    //         $provider->review_count = $provider->reviewCount();
    //     });
    //     return response()->json([
    //         'data' => $serviceProvider,
    //         'count' => count($serviceProvider),
    //         'message' => 'Service Provider list fetched successfully',
    //         'status' => true,
    //         'code' => 200
    //     ], 200);
    // }


    public function serviceProviderListBySpeciality($specialityId, $roleId)
    {
        $authUser = auth()->user();

       $excludeProviderId = null;

if ($authUser && Str::endsWith($authUser->phone, '5')) {
    $originalProvider = User::where('phone', substr($authUser->phone, 0, -1))->first();
    if ($originalProvider) {
        $excludeProviderId = $originalProvider->id;
    }
}

        $role = Role::find($roleId);
        switch (Str::lower($role->name)) {
            case 'doctor':
                $serviceProvider = User::with([
                    'doctorProfile',
                    'doctorProfile.doctorType',
                    'doctorProfile.doctorSpeciality',
                    'doctorProfile.doctorTitle',
                    'availability' => function ($query) {
                        $query->select('day', 'provider_id', 'start_time', 'end_time', 'slot_duration')
                            ->where('availability_type', 'appointment');
                    }
                ])
                    ->where('role_id', $roleId)
                    ->when($excludeProviderId, function ($query) use ($excludeProviderId) {
                        $query->where('id', '!=', $excludeProviderId);
                    })
                    ->whereHas('doctorProfile', function ($query) use ($specialityId) {
                        $query->where('doctor_speciality_id', $specialityId);
                    })
                    ->get();
                break;

            case 'lawyer':
                $serviceProvider = User::with([
                    'lawyerProfile',
                    'lawyerProfile.lawyerTitle',
                    'lawyerProfile.lawyerSpeciality',
                    'availability' => function ($query) {
                        $query->select('day', 'provider_id', 'start_time', 'end_time', 'slot_duration')
                            ->where('availability_type', 'appointment');
                    }
                ])
                    ->where('role_id', $roleId)
                    ->when($excludeProviderId, function ($query) use ($excludeProviderId) {
                        $query->where('id', '!=', $excludeProviderId);
                    })
                    ->whereHas('lawyerProfile', function ($query) use ($specialityId) {
                        $query->where('lawyer_speciality_id', $specialityId);
                    })
                    ->get();
                break;

            default:
                $serviceProvider = User::with([
                    'commonProfile',
                    'commonProfile.uniqueIdentification',
                    'commonProfile.commonSpeciality',
                    'availability' => function ($query) {
                        $query->select('day', 'provider_id', 'start_time', 'end_time', 'slot_duration')
                            ->where('availability_type', 'appointment');
                    }
                ])
                    ->where('role_id', $roleId)
                    ->when($excludeProviderId, function ($query) use ($excludeProviderId) {
                        $query->where('id', '!=', $excludeProviderId);
                    })
                    ->whereHas('commonProfile', function ($query) use ($specialityId) {
                        $query->where('common_speciality_id', $specialityId);
                    })
                    ->get();
                break;
        }

        if ($serviceProvider->isEmpty()) {
            return response()->json([
                'message' => 'No service provider found for this speciality',
                'status' => false,
                'code' => 404
            ], 404);
        }

        // Add unique customer count & ratings
        $serviceProvider->each(function ($provider) {
            $completedAppointments = Appointment::where('provider_id', $provider->id)
                ->whereRaw('LOWER(status) LIKE ?', ['%complete%'])
                ->distinct('customer_id')
                ->count('customer_id');

            $provider->customers_count = $completedAppointments;
            $provider->average_rating = $provider->averageRating();
            $provider->review_count = $provider->reviewCount();
        });

        return response()->json([
            'data' => $serviceProvider,
            'count' => count($serviceProvider),
            'message' => 'Service Provider list fetched successfully',
            'status' => true,
            'code' => 200
        ], 200);
    }







    // public function serviceProviderListBySpeciality($specialityId, $roleId)
    // {
    //     $role = Role::find($roleId);

    //     switch (Str::lower($role->name)) {
    //         case 'doctor':
    //             $serviceProvider = User::with([
    //                 'doctorProfile',
    //                 'doctorProfile.doctorType',
    //                 'doctorProfile.doctorSpeciality',
    //                 'doctorProfile.doctorTitle'
    //             ])
    //                 ->where('role_id', $roleId)
    //                 ->whereHas('doctorProfile', function ($query) use ($specialityId) {
    //                     $query->where('doctor_speciality_id', $specialityId);
    //                 })
    //                 ->get();
    //             break;

    //         case 'lawyer':
    //             $serviceProvider = User::with([
    //                 'lawyerProfile',
    //                 'lawyerProfile.lawyerTitle',
    //                 'lawyerProfile.lawyerSpeciality'
    //             ])
    //                 ->where('role_id', $roleId)
    //                 ->whereHas('lawyerProfile', function ($query) use ($specialityId) {
    //                     $query->where('lawyer_speciality_id', $specialityId);
    //                 })
    //                 ->get();
    //             break;

    //         default:
    //             $serviceProvider = User::where('role_id', $roleId)
    //                 ->whereHas('commonProfile', function ($query) use ($specialityId) {
    //                     $query->where('common_speciality_id', $specialityId);
    //                 })
    //                 ->with([
    //                     'commonProfile',
    //                     'commonProfile.uniqueIdentification',
    //                     'commonProfile.commonSpeciality'
    //                 ])
    //                 ->get();
    //             break;
    //     }

    //     // ðŸ”½ Filter out provider if the logged-in user is a customer with 12-digit phone ending in 5
    //     $loggedInUser = auth()->user();


    //     if ($loggedInUser && strlen($loggedInUser->phone) === 12 && Str::endsWith($loggedInUser->phone, '5')) {
    //         $providerPhone = substr($loggedInUser->phone, 0, -1); // Remove the last digit (5)
    //         $serviceProvider = $serviceProvider->filter(function ($provider) use ($providerPhone) {
    //             return $provider->phone !== $providerPhone;
    //         })->values(); // Reindex the collection
    //     }

    //     if ($serviceProvider->isEmpty()) {
    //         return response()->json([
    //             'message' => 'No service provider found for this speciality',
    //             'status' => false,
    //             'code' => 404
    //         ], 404);
    //     }

    //     return response()->json([
    //         'data' => $serviceProvider,
    //         'count' => count($serviceProvider),
    //         'message' => 'Service Provider list fetched successfully',
    //         'status' => true,
    //         'code' => 200
    //     ], 200);
    // }


    public function switchUser()
    {


        $user = auth()->user();
        $role = Str::lower($user->role->name);
        if (!in_array($role, ['customer', 'super admin', 'moderator'])) {
            $matchUser = User::with(['customerProfile', 'wallet', 'languageState', 'role'])->where('phone', "{$user->phone}5")->first();
            if ($matchUser) {
                return response()->json([
                    'user' => $matchUser,
                    'token' => $matchUser->createToken('carekori-token')->plainTextToken,
                    'message' => 'Provider switched successfully to Existing Customer',
                    'status' => true,
                    'code' => 200
                ], 200);
            } else {
                $user->load(
                    match ($role) {
                        'doctor' => ['doctorProfile.doctorType', 'doctorProfile.doctorSpeciality', 'doctorProfile.doctorTitle'],
                        'lawyer' => ['lawyerProfile.lawyerTitle', 'lawyerProfile.lawyerSpeciality'],

                        default => ['commonProfile.uniqueIdentification', 'commonProfile.commonSpeciality'],
                    }
                );

                if ($role === 'doctor') {
                    $profile = [
                        'gender' => $user->doctorProfile->gender ?? "N/A",
                        'dob' => $user->doctorProfile->dob ?? "N/A",
                        'district' => $user->doctorProfile->district ?? "N/A",
                        'sub_district' => $user->doctorProfile->thana ?? "N/A",
                        'union_name' =>  "N/A",
                        'avatar' => $user->doctorProfile->avatar ?? null, // Store the avatar URL
                    ];
                } elseif ($role === 'lawyer') {
                    $profile = [
                        'gender' => $user->lawyerProfile->gender ?? "N/A",
                        'dob' => $user->lawyerProfile->dob ?? "N/A",
                        'district' => $user->lawyerProfile->district ?? "N/A",
                        'sub_district' => $user->lawyerProfile->thana ?? "N/A",
                        'union_name' =>  "N/A",
                        'avatar' => $user->lawyerProfile->avatar ?? null, // Store the avatar URL
                    ];
                } else {
                    $profile = [
                        'gender' => $user->commonProfile->gender ?? "N/A",
                        'dob' => $user->commonProfile->dob ?? "N/A",
                        'district' => $user->commonProfile->district ?? "N/A",
                        'sub_district' => $user->commonProfile->thana ?? "N/A",
                        'union_name' =>  "N/A",
                        'avatar' => $user->commonProfile->avatar ?? null, // Store the avatar URL
                    ];
                }


                $role_id = Role::where('name', 'customer')->value('id');
                DB::beginTransaction();
                try {
                    $uniqueUserId = $this->generateUniqueUserId();

                    $newUser = User::create([
                        'name' => $user->name,
                        'email' => $user->email ? "{$user->email}5" : null,
                        'phone' => "{$user->phone}5",
                        'password' => Hash::make(Str::random(10)),
                        'role_id' => $role_id,
                        'unique_user_id' => (string)$uniqueUserId, // Ensure unique_user_id is passed here
                    ]);


                    $newUser->customerProfile()->create($profile);

                    $newUser->wallet()->create([
                        'balance' => 0, // Initialize the wallet balance to 0
                    ]);

                    $newUser->languageState()->create([
                        'state' => 'bn',
                    ]);

                    DB::commit();

                    $newUser->load(['role', 'customerProfile', 'wallet', 'languageState']);


                    return response()->json([
                        'user' => $newUser,
                        'token' => $newUser->createToken('carekori-token')->plainTextToken,
                        'message' => 'Provider switched successfully to New Customer',
                        'status' => true,
                        'code' => 200,
                    ], 200);
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('User Creation error: ' . $e->getMessage());  // Log the error
                    return response()->json(['error' => 'User Creation failed', 'details' => $e->getMessage()], 500); // Return the error details
                }
            }
        } else {

            $provider = User::with('role')->where('phone', substr($user->phone, 0, -1))->first();

            $provider_role = Str::lower($provider->role->name);

            $provider->load(
                match ($provider_role) {
                    'doctor' => ['doctorProfile.doctorType', 'doctorProfile.doctorSpeciality', 'doctorProfile.doctorTitle'],
                    'lawyer' => ['lawyerProfile.lawyerTitle', 'lawyerProfile.lawyerSpeciality'],
                    default => ['commonProfile.uniqueIdentification', 'commonProfile.commonSpeciality'],
                }
            );

            if ($provider) {
                return response()->json([
                    'user' => $provider,
                    'token' => $provider->createToken('carekori-token')->plainTextToken,
                    'message' => 'Customer switched successfully to Provider',
                    'status' => true,
                    'code' => 200
                ], 200);
            } else {
                return response()->json(['error' => 'Provider not found'], 404);
            }
        }
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
