<?php

use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\CommonProvider;
use App\Http\Controllers\API\CustomerProfile;
use App\Http\Controllers\API\DoctorProfileCOntroller;
use App\Http\Controllers\API\DoctorSpecialityController;
use App\Http\Controllers\API\DoctorTitle;
use App\Http\Controllers\API\DoctorType;
use App\Http\Controllers\API\LawyerProfileController;
use App\Http\Controllers\API\LawyerTitleController;
use App\Http\Controllers\API\RoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\OtpController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\LoginController;
use App\Http\Controllers\API\ModeratorProfile;
use App\Http\Controllers\API\UnAuthenticatedController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    

   

  




    //super admin
    Route::middleware('superadmin')->group(function () {
        Route::post('/create-roles', [RoleController::class, 'createRole']);
        Route::post('/create-moderator', [AdminController::class, 'create_moderator']);

        //for customer only
        Route::get('/user/{uniqueUserId}', [AdminController::class, 'getUserWithProfile']);
        Route::put('/user/{uniqueUserId}', [AdminController::class, 'updateUser']);
        Route::delete('/user/{uniqueUserId}', [AdminController::class, 'deleteUser']);
        Route::put('/user/{uniqueUserId}/deactivate', [AdminController::class, 'deactivateUser']);
        Route::get('/all-user', [AdminController::class, 'getAllUsers']);


        Route::get('/all-moderators', [AdminController::class, 'getAllModerators']);
        Route::get('/moderator/{uniqueModeratorId}', [AdminController::class, 'getSingleModerator']);
        Route::delete('/delete-moderator/{uniqueModeratorId}', [AdminController::class, 'deleteModerator']);
        Route::put('/update-moderator/{uniqueModeratorId}', [AdminController::class, 'updateModerator']);


        Route::get('/all-roles', [AdminController::class, 'getAllRoles']);
        Route::get('/role/{roleId}', [AdminController::class, 'getSingleRole']);
        Route::delete('/delete-role/{roleId}', [AdminController::class, 'deleteRole']);
        Route::put('/update-role/{roleId}', [AdminController::class, 'updateRole']);


        Route::get('/doctor-types', [DoctorType::class, 'index']);
        Route::get('/doctor-types/{id}', [DoctorType::class, 'show']);
        Route::post('/doctor-types', [DoctorType::class, 'store']);
        Route::put('/doctor-types/{id}', [DoctorType::class, 'update']);
        Route::delete('/doctor-types/{id}', [DoctorType::class, 'destroy']);



        Route::get('/doctor-specialities', [DoctorSpecialityController::class, 'index']);
        Route::get('/doctor-specialities/{id}', [DoctorSpecialityController::class, 'show']);
        Route::post('/doctor-specialities', [DoctorSpecialityController::class, 'store']);
        Route::put('/doctor-specialities/{id}', [DoctorSpecialityController::class, 'update']);
        Route::delete('/doctor-specialities/{id}', [DoctorSpecialityController::class, 'destroy']);

        Route::get('/doctor-titles', [DoctorTitle::class, 'index']);
        Route::get('/doctor-titles/{id}', [DoctorTitle::class, 'show']);
        Route::post('/doctor-titles', [DoctorTitle::class, 'store']);
        Route::put('/doctor-titles/{id}', [DoctorTitle::class, 'update']);
        Route::delete('/doctor-titles/{id}', [DoctorTitle::class, 'destroy']);


       


        Route::post('/create-service-provider', [AdminController::class, 'createServiceProviderRole']);
    });


    //Doctor
    Route::middleware('doctor')->group(function () {
        Route::put('/doctor-update', [DoctorProfileCOntroller::class, 'update']);
        Route::get('/doctor-profile', [DoctorProfileCOntroller::class, 'show']);

        Route::post('doctor/profile/image', [DoctorProfileCOntroller::class, 'addProfileImage']);
        Route::put('doctor/profile/image', [DoctorProfileCOntroller::class, 'updateProfileImage']);

        // Update only pricing
        Route::put('/doctor-profile/pricing', [DoctorProfileController::class, 'updatePricing']);

        // Update only availability status
        Route::put('/doctor-profile/availability', [DoctorProfileController::class, 'updateAvailability']);
    });

   




    // You can add post/put/delete routes too
});

Route::post('/otp/send', [OtpController::class, 'sendOtp']);
Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);

Route::get('/all-roles-service-provider-customer', [UnAuthenticatedController::class, 'getAllRolesOfServiceProviderAndCustomer']);
Route::get('/get-identification-placeholder/{roleId}', [UnAuthenticatedController::class, 'getIdentificationPlaceholder']);