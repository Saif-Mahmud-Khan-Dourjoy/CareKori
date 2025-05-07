<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $response=[];

        switch ($user->role->name) {
            case 'doctor':
                $response['profile_info'] = $user->doctorProfile;
                break;

            case 'lawyer':
                $response['profile_info'] = $user->lawyerProfile;
                break;

            case 'customer':
                $response['profile_info'] = $user->customerProfile;
                break;
            case 'moderator':
                $response['profile_info'] = $user->moderatorProfile;
                break;
        }

        $response['user'] = $user;
        $response['role'] = $user->role->name;
        $response['token'] = $user->createToken('carekori-token')->plainTextToken;




        return response()->json([
            'data' => $response,
            'message' => 'Login successful',
            'status' => true,
            'code' => 200
        ]);
    }


   
}