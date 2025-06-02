<?php

use App\Http\Controllers\API\AddBannerController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\Appoinment\AppointmentController;
use App\Http\Controllers\API\Appoinment\ServiceProviderController;
use App\Http\Controllers\API\CommonProvider;
use App\Http\Controllers\API\CommonProviderSpeciality;
use App\Http\Controllers\API\CustomerProfile;
use App\Http\Controllers\API\DoctorProfileCOntroller;
use App\Http\Controllers\API\DoctorSpecialityController;
use App\Http\Controllers\API\DoctorTitle;
use App\Http\Controllers\API\DoctorType;
use App\Http\Controllers\API\Document\DocumentController;
use App\Http\Controllers\API\LanguageStateController;
use App\Http\Controllers\API\LawyerProfileController;
use App\Http\Controllers\API\LawyerSpecialityController;
use App\Http\Controllers\API\LawyerTitleController;
use App\Http\Controllers\API\Review\ReviewController;
use App\Http\Controllers\API\RoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\OtpController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\LoginController;
use App\Http\Controllers\API\ModeratorProfile;
use App\Http\Controllers\API\ProviderController;
use App\Http\Controllers\API\ServiceProvider;
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


    //customer

    Route::middleware('customer')->group(function () {
        Route::put('/customer-update', [CustomerProfile::class, 'update']);
        Route::get('/customer-profile', [CustomerProfile::class, 'show']);
        Route::post('/check-password', [CustomerProfile::class, 'checkPassword']);

        Route::post('/profile/image', [CustomerProfile::class, 'addProfileImage']);
        Route::post('/update/profile/image', [CustomerProfile::class, 'updateProfileImage']);

        Route::post('/appointments', [AppointmentController::class, 'bookAppointment']);
        // Route::put('/appointments/{appointmentId}', [AppointmentController::class, 'updateAppointment']);
        Route::get('/appointments/user/{uniqueUserId}', [AppointmentController::class, 'getAppointmentsByUser']);
        Route::get('/appointments/user/upcoming/{uniqueUserId}', [AppointmentController::class, 'upcomingAppointmentsForUser']);


        
        Route::get('/cancel/appointments/{appointmentId}', [AppointmentController::class, 'cancelAppointmentWithinTime']);
        Route::get('/check-availability/{provider_unique_user_id}/{appointment_date}', [AppointmentController::class, 'checkAvailability']);


        Route::post('/phone/change/otp/send', [OtpController::class, 'sendOtpForPhoneChange']); // Send OTP for phone number change
        Route::post('/otp/verify/update/phone', [OtpController::class, 'verifyOtpAndChangePhone']); // Verify OTP and update phone number

      





        Route::get('/language-state', [LanguageStateController::class, 'getLanguageState']);
        Route::post('/language-state', [LanguageStateController::class, 'createOrUpdateLanguageState']);


        Route::get('/document/list-provider-members/{roleId}', [DocumentController::class, 'listProviderMembers']);
        Route::get('/providers/{providerId}/documents', [DocumentController::class, 'getDocumentsBetweenUsers']);
        Route::get('/documents/{documentId}/download', [DocumentController::class, 'downloadDocument']);
        Route::get('/documents/{providerId}/download-all', [DocumentController::class, 'downloadAllDocuments']);
    });

    //moderator

    Route::middleware('moderator')->group(function () {
        Route::put('/moderator-update', [ModeratorProfile::class, 'update']);
        Route::get('/moderator-profile', [ModeratorProfile::class, 'show']);

        Route::post('/moderator/profile/image', [ModeratorProfile::class, 'addProfileImage']);
        Route::post('/update/moderator/profile/image', [ModeratorProfile::class, 'updateProfileImage']);
    });

    //common provider

    Route::middleware('commonprovider')->group(function () {
        Route::put('/common-provider-update', [CommonProvider::class, 'update']);
        Route::get('/common-provider-profile', [CommonProvider::class, 'show']);

        Route::post('/common-provider/profile/image', [CommonProvider::class, 'addProfileImage']);
        Route::post('/update/common-provider/profile/image', [CommonProvider::class, 'updateProfileImage']);

        // Update only pricing
        Route::put('/common-provider-profile/pricing', [CommonProvider::class, 'updatePricing']);

        // Update only availability status
        Route::put('/common-provider-profile/availability', [CommonProvider::class, 'updateAvailability']);
    });


    // all service provider

    Route::middleware('provider')->group(function () {
        Route::post('/providers/schedule', [ServiceProviderController::class, 'storeAvailability']);
        Route::put('/providers/schedule/{id}', [ServiceProviderController::class, 'updateAvailability']);
        Route::delete('/providers/schedule/{id}', [ServiceProviderController::class, 'deleteAvailability']);
        Route::get('/appointments/provider/{uniqueUserId}', [AppointmentController::class, 'getAppointmentsByProvider']);


        Route::get('/appointments/provider/upcoming/{uniqueUserId}', [AppointmentController::class, 'upcomingAppointmentsForProvider']);
        // Route::delete('/appointments/{appointmentId}', [AppointmentController::class, 'deleteAppointment']);
        // Route::put('/appointments/{appointmentId}', [AppointmentController::class, 'updateAppointment']);
        Route::put('/appointments/status/{appointmentId}', [AppointmentController::class, 'updateAppointmentStatus']);
       
    });






    //super admin
    Route::middleware('superadmin')->group(function () {
        Route::post('/create-roles', [RoleController::class, 'createRole']);
        Route::post('/create-moderator', [AdminController::class, 'create_moderator']);

        //for customer only
        Route::get('/customer/{uniqueUserId}', [AdminController::class, 'getUserWithProfile']);
        Route::put('/customer/{uniqueUserId}', [AdminController::class, 'updateUser']);
        Route::delete('/customer/{uniqueUserId}', [AdminController::class, 'deleteUser']);
        Route::put('/customer/{uniqueUserId}/deactivate', [AdminController::class, 'deactivateUser']);
        Route::get('/all-customer', [AdminController::class, 'getAllUsers']);


        Route::get('/all-moderators', [AdminController::class, 'getAllModerators']);
        Route::get('/moderator/{uniqueModeratorId}', [AdminController::class, 'getSingleModerator']);
        Route::delete('/delete-moderator/{uniqueModeratorId}', [AdminController::class, 'deleteModerator']);
        Route::put('/update-moderator/{uniqueModeratorId}', [AdminController::class, 'updateModerator']);


        Route::get('/all-roles', [AdminController::class, 'getAllRoles']);
        Route::get('/role/{roleId}', [AdminController::class, 'getSingleRole']);
        Route::delete('/delete-role/{roleId}', [AdminController::class, 'deleteRole']);
        Route::post('/update-role/{roleId}', [AdminController::class, 'updateRole']);


        Route::get('/doctor-types', [DoctorType::class, 'index']);
        Route::get('/doctor-types/{id}', [DoctorType::class, 'show']);
        Route::post('/doctor-types', [DoctorType::class, 'store']);
        Route::put('/doctor-types/{id}', [DoctorType::class, 'update']);
        Route::delete('/doctor-types/{id}', [DoctorType::class, 'destroy']);



        Route::get('/doctor-specialities', [DoctorSpecialityController::class, 'index']);
        Route::get('/doctor-specialities/{id}', [DoctorSpecialityController::class, 'show']);
        Route::post('/doctor-specialities', [DoctorSpecialityController::class, 'store']);
        Route::post('/update/doctor-specialities/{id}', [DoctorSpecialityController::class, 'update']);
        Route::delete('/doctor-specialities/{id}', [DoctorSpecialityController::class, 'destroy']);

        Route::get('/doctor-titles', [DoctorTitle::class, 'index']);
        Route::get('/doctor-titles/{id}', [DoctorTitle::class, 'show']);
        Route::post('/doctor-titles', [DoctorTitle::class, 'store']);
        Route::put('/doctor-titles/{id}', [DoctorTitle::class, 'update']);
        Route::delete('/doctor-titles/{id}', [DoctorTitle::class, 'destroy']);

        Route::get('/lawyer-titles', [LawyerTitleController::class, 'index']);
        Route::get('/lawyer-titles/{id}', [LawyerTitleController::class, 'show']);
        Route::post('/lawyer-titles', [LawyerTitleController::class, 'store']);
        Route::put('/lawyer-titles/{id}', [LawyerTitleController::class, 'update']);
        Route::delete('/lawyer-titles/{id}', [LawyerTitleController::class, 'destroy']);


        Route::get('/lawyer-specialities', [LawyerSpecialityController::class, 'index']);
        Route::get('/lawyer-specialities/{id}', [LawyerSpecialityController::class, 'show']);
        Route::post('/lawyer-specialities', [LawyerSpecialityController::class, 'store']);
        Route::post('/update/lawyer-specialities/{id}', [LawyerSpecialityController::class, 'update']);
        Route::delete('/lawyer-specialities/{id}', [LawyerSpecialityController::class, 'destroy']);



        Route::post('/create-service-provider', [AdminController::class, 'createServiceProviderRole']);

        //approve status
        Route::put('/approve-provider/{uniqueUserId}', [AdminController::class, 'approveProvider']);

       
       

        //common provider speciality
        Route::post('/create-common-provider-speciality', [CommonProviderSpeciality::class, 'addCommonProviderSpeciality']);

        Route::get('/common-provider-specialities/{id}', [CommonProviderSpeciality::class, 'getCommonProviderSpecialityById']);
        Route::post('/update/common-provider-specialities/{id}', [CommonProviderSpeciality::class, 'updateCommonProviderSpeciality']);
        Route::delete('/common-provider-specialities/{id}', [CommonProviderSpeciality::class, 'deleteCommonProviderSpeciality']);




        Route::delete('/appointments/{appointmentId}', [AppointmentController::class, 'deleteAppointment']);
    });


    //Doctor
    Route::middleware('doctor')->group(function () {
        Route::put('/doctor-update', [DoctorProfileCOntroller::class, 'update']);
        Route::get('/doctor-profile', [DoctorProfileCOntroller::class, 'show']);

        Route::post('/doctor/profile/image', [DoctorProfileCOntroller::class, 'addProfileImage']);
        Route::post('/update/doctor/profile/image', [DoctorProfileCOntroller::class, 'updateProfileImage']);

        // Update only pricing
        Route::put('/doctor-profile/pricing', [DoctorProfileController::class, 'updatePricing']);

        // Update only availability status
        Route::put('/doctor-profile/availability', [DoctorProfileController::class, 'updateAvailability']);
    });

    // Lawyer
    Route::middleware('lawyer')->group(function () {
        Route::put('/lawyer-update', [LawyerProfileController::class, 'update']);
        Route::get('/lawyer-profile', [LawyerProfileController::class, 'show']);

        Route::post('/lawyer/profile/image', [LawyerProfileController::class, 'addProfileImage']);
        Route::post('/update/lawyer/profile/image', [LawyerProfileController::class, 'updateProfileImage']);

        // Update only pricing
        Route::put('/lawyer-profile/pricing', [LawyerProfileController::class, 'updatePricing']);

        // Update only availability status
        Route::put('/lawyer-profile/availability', [LawyerProfileController::class, 'updateAvailability']);
    });


    // Documents
    Route::post('/document/upload', [DocumentController::class, 'upload']);
   
    Route::post('/document/upload-multiple', [DocumentController::class, 'uploadMultiple']);
    
    Route::get('/document/user/{uniqueId}', [DocumentController::class, 'getVerificationDocuments']);
    Route::get('/document/user/{uniqueId}/public', [DocumentController::class, 'getPublicDocuments']);
    Route::get('/document/appointment/{id}', [DocumentController::class, 'getDocumentsByAppointment']);
    Route::get('/document/private/customer/{customerUniqueId}/provider/{providerUniqueId}', [DocumentController::class, 'getPrivateDocumentsForCustomer']);
    Route::get('/document/private/provider/{providerUniqueId}/customer/{customerUniqueId}', [DocumentController::class, 'getPrivateDocumentsForProvider']);

    // Review   
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}/status', [ReviewController::class, 'updateStatus']);
    Route::get('/service-providers/{id}/reviews', [ReviewController::class, 'getApprovedReviews']);

    //ADD BANNER

    Route::post('banner/store', [AddBannerController::class, 'store']);  // Store a new banner
    Route::get('banner/latest', [AddBannerController::class, 'getLatest']);  // Get the latest banner
    Route::get('banner/all', [AddBannerController::class, 'getAll']);  // Get all banners


    // Common Provider Speciality
    Route::get('/common-provider-specialities/{roleId}', [CommonProviderSpeciality::class, 'getCommonProviderSpecialitiesByRoleId']);



    Route::get('/providers/search', [ProviderController::class, 'search']);



    // You can add post/put/delete routes too
});

Route::post('/otp/send', [OtpController::class, 'sendOtp']);
Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);

Route::get('/all-roles-service-provider-customer', [UnAuthenticatedController::class, 'getAllRolesOfServiceProviderAndCustomer']);
Route::get('/get-identification-placeholder/{roleId}', [UnAuthenticatedController::class, 'getIdentificationPlaceholder']);

Route::put('/reset-password', [UnAuthenticatedController::class, 'resetPassword']);

Route::post('/otp/resend', [OtpController::class, 'resendOtp']);



Route::get('/all-service-provider', [ServiceProvider::class, 'getServiceProvider']);
Route::get('/service-provider-speciality/{roleId}', [ServiceProvider::class, 'serviceProviderSpeciality']);
Route::get('/service-providers-list/{specialityId}/{roleId}', [ServiceProvider::class, 'serviceProviderListBySpeciality']);


Route::post('/forget-password/otp/send', [OtpController::class, 'sendOtpForForgetPassword']);
Route::post('/forget-password/otp/verify', [OtpController::class, 'verifyOtpForForgetPassword']);
Route::post('/forget-password/update', [OtpController::class, 'updatePasswordAfterForget']);