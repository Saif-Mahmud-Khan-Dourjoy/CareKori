<?php

use App\Http\Controllers\API\AdminController;
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
    //customer


    //super admin
    Route::middleware('superadmin')->group(function () {
        Route::post('/create-roles', [RoleController::class, 'createRole']);
        Route::post('/create-moderator', [AdminController::class, 'create_moderator']);
        Route::get('/user/{userId}', [AdminController::class, 'getUserWithProfile']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
        Route::put('/users/{user}/deactivate', [AdminController::class, 'deactivateUser']);
        Route::get('/all-user', [AdminController::class, 'getAllUsers']);


        Route::get('/all-moderators', [AdminController::class, 'getAllModerators']);
        Route::get('/moderator/{moderatorId}', [AdminController::class, 'getSingleModerator']);
        Route::delete('/delete-moderator/{moderatorId}', [AdminController::class, 'deleteModerator']);
        Route::put('/update-moderator/{moderatorId}', [AdminController::class, 'updateModerator']);


        Route::get('/all-roles', [AdminController::class, 'getAllRoles']);
        Route::get('/role/{roleId}', [AdminController::class, 'getSingleRole']);
        Route::delete('/delete-role/{roleId}', [AdminController::class, 'deleteRole']);
        Route::put('/update-role/{roleId}', [AdminController::class, 'updateRole']);
    });




    // You can add post/put/delete routes too
});

Route::post('/otp/send', [OtpController::class, 'sendOtp']);
Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
