<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'phone' => 'required',
    //         'password' => 'required'
    //     ]);

    //     $user = User::where('phone', $request->phone)->first();

    //     if (! $user || ! Hash::check($request->password, $user->password)) {
    //         return response()->json(['message' => 'Invalid credentials'], 401);
    //     }

    //     $response=[];

    //     switch ($user->role->name) {
    //         case 'doctor':
    //             $response['profile_info'] = $user->doctorProfile;
    //             break;

    //         case 'lawyer':
    //             $response['profile_info'] = $user->lawyerProfile;
    //             break;

    //         case 'customer':
    //             $response['profile_info'] = $user->customerProfile;
    //             break;
    //         case 'moderator':
    //             $response['profile_info'] = $user->moderatorProfile;
    //             break;
    //     }

    //     $response['user'] = $user;
    //     $response['role'] = $user->role->name;
    //     $response['token'] = $user->createToken('carekori-token')->plainTextToken;




    //     return response()->json([
    //         'data' => $response,
    //         'message' => 'Login successful',
    //         'status' => true,
    //         'code' => 200
    //     ]);
    // }


    // public function login(Request $request)
    // {
    //     // Validate incoming request
    //     $request->validate([
    //         'phone' => 'required',
    //         'password' => 'required'
    //     ]);

    //     // Retrieve user with related role and potential profile data
    //     $user = User::with([
    //         'role',
    //         'customerProfile',
    //         'moderatorProfile',
    //         'doctorProfile' => function ($query) {
    //             $query->with(['doctorType', 'doctorSpeciality', 'doctorTitle']);
    //         },
    //         'lawyerProfile' => function ($query) {
    //             $query->with('lawyerTitle');
    //         },
    //         'commonProfile' => function ($query) {
    //             $query->with('uniqueIdentification');
    //         }
    //     ])->where('phone', $request->phone)->first();
    //     // Check if user exists and password matches
    //     if (! $user || ! Hash::check($request->password, $user->password)) {
    //         return response()->json([
    //             'message' => 'Invalid credentials',
    //             'status' => false,
    //             'code' => 401
    //         ], 401);
    //     }

    //     // Build response using match expression for profile
    //     return response()->json([
    //         'user' => [
    //             'id' => $user->id,
    //             'name' => $user->name,
    //             'phone' => $user->phone,
    //             'role' => ["name" => $user->role->name, "id" => $user->role->id],
    //             'email' => $user->email,
    //             'profile' => match ($user->role->name) {
    //                 'doctor'   => $user->doctorProfile,
    //                 'lawyer'   => $user->lawyerProfile,
    //                 'customer' => $user->customerProfile,
    //                 'moderator' => $user->moderatorProfile,
    //                 'super admin' => null,
    //                 default    => $user->commonProfile,
    //             },
    //         ],
    //         'token' => $user->createToken('carekori-token')->plainTextToken,
    //         'message' => 'Login successful',
    //         'status' => true,
    //         'code' => 200,
    //     ], 200);
    // }

    public function login(Request $request)
    {
        // Step 1: Validate input
        $request->validate([
            'phone' => 'required',
            'password' => 'required',
        ]);

        // Step 2: Load user with role only
        $user = User::with('role')->where('phone', $request->phone)->first();

        // Step 3: Check credentials
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
                'status' => false,
                'code' => 401
            ], 401);
        }

        // Step 4: Determine role
        $role = Str::lower($user->role->name);

        // Step 5: Dynamically load only required profile based on role
        $user->load(
            match ($role) {
                'doctor' => ['doctorProfile.doctorType', 'doctorProfile.doctorSpeciality', 'doctorProfile.doctorTitle'],
                'lawyer' => ['lawyerProfile.lawyerTitle', 'lawyerProfile.lawyerSpeciality'],
                'customer' => ['customerProfile','wallet', 'languageState'],
                'moderator' => ['moderatorProfile'],
                'super admin'=>[],
                
                default => ['commonProfile.uniqueIdentification', 'commonProfile.commonSpeciality'],
            }
        );

        $user->makeHidden('id');

        // Step 6: Select appropriate profile
        // $profile = match ($role) {
        //     'doctor' => $user->doctorProfile,
        //     'lawyer' => $user->lawyerProfile,
        //     'customer' => $user->customerProfile,
        //     'moderator' => $user->moderatorProfile,
        //     'super admin' => [],
        //     default => $user->commonProfile,
        // };

        // Step 7: Return the res'super admin' => [],ponse
        return response()->json([
            // 'user' => [
            //     'unique_user_id' => $user->unique_user_id,
            //     'name' => $user->name,
            //     'phone' => $user->phone,
            //     'email' => $user->email,
            //     'role' => ['name' => $user->role->name, 'id' => $user->role->id],
            //     'profile' => $profile,
            // ],
            'user'=> $user,
            'token' => $user->createToken('carekori-token')->plainTextToken,
            'message' => 'Login successful',
            'status' => true,
            'code' => 200,
        ]);
    }

    public function refreshToken(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Revoke current token
        $user->currentAccessToken()->delete();

        // Issue new token
        $tokenResult = $user->createToken('carekori-token');

        $expirationMinutes = env('SANCTUM_TOKEN_EXPIRATION', 60);
        $expiresAt = Carbon::now()->addMinutes($expirationMinutes);
        
    

        return response()->json([
            'access_token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }


    public function checkToken(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['valid' => false, 'message' => 'Unauthenticated'], 401);
        }

        $token = $user->currentAccessToken();

        if (!$token) {
            return response()->json(['valid' => false, 'message' => 'Token not found'], 401);
        }

        $expirationMinutes = env('SANCTUM_TOKEN_EXPIRATION', 60);

        $expiresAt = $token->created_at->addMinutes($expirationMinutes);

        if ($expiresAt->isPast()) {
            return response()->json(['valid' => false, 'message' => 'Token expired'], 401);
        }

        return response()->json(['valid' => true, 'message' => 'Token is valid']);
    }
    
}