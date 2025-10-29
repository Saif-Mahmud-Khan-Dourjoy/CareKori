<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    public function store(Request $request)
    {
        // Validate incoming data
        $validated = $request->validate([
            'provider_unique_user_id' => 'required|exists:users,unique_user_id',
            'complaint_text' => 'nullable|string',
            'is_rude' => 'required|boolean',
            'is_late' => 'required|boolean',
            'interrupted' => 'required|boolean',
            'appointment_id' => 'required|exists:appointments,id',
        ]);

        // Ensure the provider exists
        $provider = User::where('unique_user_id', $validated['provider_unique_user_id'])->first();
        if (!$provider) {
            return response()->json(['message' => 'Provider not found'], 404);
        }

        // Ensure the appointment exists and belongs to the logged-in user
        $appointment = Appointment::find($validated['appointment_id']);
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }
        if ($appointment->provider_id != $provider->id) {
            return response()->json(['message' => 'Provider not attached with appointment'], 403);
        }
        if (Auth::id() != $appointment->customer_id) {
            return response()->json(['message' => 'You can only complain about your own appointments '], 403);
        }

        // Convert the boolean values to actual booleans
        $isRude = filter_var($validated['is_rude'], FILTER_VALIDATE_BOOLEAN);
        $isLate = filter_var($validated['is_late'], FILTER_VALIDATE_BOOLEAN);
        $interrupted = filter_var($validated['interrupted'], FILTER_VALIDATE_BOOLEAN);

        // Ensure the provider is different from the logged-in user
        if (Auth::id() === (int) $provider->id) {
            return response()->json(['message' => 'You cannot complain about yourself'], 400);
        }
        $existingComplaint = Complaint::where('appointment_id', $validated['appointment_id'])->first();
        if ($existingComplaint) {
            return response()->json([
                'message' => 'A report for this appointment has already been submitted.'
            ], 409);
        }
        // Create the complaint
        $complaint = Complaint::create([
            'user_id' => Auth::id(),
            'provider_id' => $provider->id,
            'complaint_text' => $validated['complaint_text'],
            'is_rude' => $isRude,
            'is_late' => $isLate,
            'interrupted' => $interrupted,
            'appointment_id' => $validated['appointment_id'],
        ]);

        // Return success response
        return response()->json([
            'message' => 'Complaint submitted successfully.',
            'complaint' => $complaint
        ], 201);
    }



    public function getAllComplaints()
    {
        // Fetch all complaints from the database
        $complaints = Complaint::with([
            'provider',
            'provider.role',
            
            'provider.doctorProfile.doctorSpeciality',
      
            'provider.lawyerProfile.lawyerSpeciality',
            'provider.commonProfile.commonSpeciality',
            'user',
            'user.customerProfile',
            'appointment'
        ])->get();

        
$complaints->transform(function ($complaint) {
    $provider = $complaint->provider;
    $role = strtolower($provider->role->name ?? '');

    if ($role === 'doctor') {
        $profile = $provider->doctorProfile;
        $speciality = $profile?->doctorSpeciality;
        $type= $role;
    } elseif ($role === 'lawyer') {
        $profile = $provider->lawyerProfile;
        $speciality = $profile?->lawyerSpeciality;
        $type=$role;
    } else {
        $profile = $provider->commonProfile;
        $speciality = $profile?->commonSpeciality;
        $type=$role;
    }

    $complaint->provider_profile = $profile;
    $complaint->provider_speciality = $speciality;
    $complaint->provider_type = $type;


    

    return $complaint;
});

        // Return the complaints as a response
        return response()->json([
            'complaints' => $complaints
        ]);
    }


    public function getComplaintsForProvider($providerUniqueId)
    {

        $provider = User::findByUniqueUserId($providerUniqueId);

        if (!$provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }



        // Get all complaints for the provider
        $complaints = Complaint::with(['user', 'appointment'])->where('provider_id', $provider->id)->get();

        return response()->json([
            'provider' => $provider,
            'complaints' => $complaints
        ]);
    }

    // Get all complaints for a specific user for a specific provider
    public function getUserProviderComplaints($providerUniqueId)
    {


        // Ensure the provider exists
        $provider = User::findByUniqueUserId($providerUniqueId);
        if (!$provider) {
            return response()->json(['message' => 'Provider not found'], 404);
        }

        // Get all complaints for this user and provider combination
        $complaints = Complaint::with(['provider', 'appointment'])->where('user_id', Auth::id())
            ->where('provider_id', $provider->id)
            ->get();

        // If there are no complaints, return an empty response
        if ($complaints->isEmpty()) {
            return response()->json(['message' => 'No complaints found for this user and provider combination'], 404);
        }

        return response()->json([


            'complaints' => $complaints
        ]);
    }

    // Get all complaints for a specific appointment
    // public function getComplaintsForAppointmentByCustomer($appointmentId)
    // {
    //     // Ensure the appointment exists
    //     $appointment = Appointment::find($appointmentId);
    //     if (!$appointment) {
    //         return response()->json(['message' => 'Appointment not found'], 404);
    //     }

    //     // Eager load the 'user' and 'provider' relationships with the complaints
    //     $complaints = $appointment->complaints()
    //         ->with(['user', 'provider']) // Eager load both user and provider
    //         ->get();

    //     // If no complaints exist, return an appropriate message
    //     if ($complaints->isEmpty()) {
    //         return response()->json(['message' => 'No complaints found for this appointment'], 404);
    //     }

    //     // Return the complaints and appointment details
    //     return response()->json([
    //         'appointment' => $appointment,
    //         'complaints' => $complaints
    //     ]);
    // }

    public function getComplaintsForAppointmentByCustomer($appointmentId)
    {

        $appointment = Appointment::find($appointmentId);
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }


        $user = Auth::user();


        if ($user->id !== $appointment->provider_id && $user->id !== $appointment->user_id) {
            return response()->json(['message' => 'You are not involved in this appointment'], 403);
        }

        $reportedBy = $user->id === $appointment->user_id ? 'user' : 'provider';

        $complaint = Complaint::with(['user', 'provider'])
            ->where('appointment_id', $appointmentId)
            ->where('reported_by', $reportedBy)
            ->first();


        if (!$complaint) {
            return response()->json(['message' => 'No complaint filed by customer for this appointment'], 404);
        }

        // Return the complaint details
        return response()->json([
            'complaint' => $complaint,
        ]);
    }



    public function storeByProvider(Request $request)
    {

        $validated = $request->validate([
            'user_unique_user_id' => 'required|exists:users,unique_user_id',
            'complaint_text'      => 'nullable|string',
            'is_rude'             => 'required|boolean',
            'is_late'             => 'required|boolean',
            'interrupted'         => 'required|boolean',
            'appointment_id'      => 'required|exists:appointments,id',
        ]);

        // Auth provider
        $provider = Auth::user();
        if (!$provider) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Target customer
        $customer = User::where('unique_user_id', $validated['user_unique_user_id'])->first();
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }


        $appointment = Appointment::find($validated['appointment_id']);
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }
        if ((int)$appointment->provider_id !== (int)$provider->id) {
            return response()->json(['message' => 'You are not the provider for this appointment'], 403);
        }
        if ((int)$appointment->user_id !== (int)$customer->id) {
            return response()->json(['message' => 'This customer is not attached to the appointment'], 403);
        }


        if ((int)$provider->id === (int)$customer->id) {
            return response()->json(['message' => 'You cannot complain about yourself'], 400);
        }


        $exists = Complaint::where('appointment_id', $appointment->id)
            ->where('reported_by', 'provider')
            ->first();

        if ($exists) {
            return response()->json([
                'message' => 'A provider-side report for this appointment has already been submitted.'
            ], 409);
        }

        $complaint = Complaint::create([
            'user_id'         => $customer->id,
            'provider_id'     => $provider->id,
            'complaint_text'  => $validated['complaint_text'] ?? null,
            'is_rude'         => (bool)$validated['is_rude'],
            'is_late'         => (bool)$validated['is_late'],
            'interrupted'     => (bool)$validated['interrupted'],
            'appointment_id'  => $appointment->id,
            'reported_by'     => 'provider',
        ]);

        return response()->json([
            'message'   => 'Complaint submitted successfully.',
            'complaint' => $complaint,
        ], 201);
    }



    public function getComplaintForAppointmentByProvider($appointmentId)
    {
        // Ensure the appointment exists
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        // Get the logged-in user
        $user = Auth::user();

        // Ensure the user is either the provider or the customer for this appointment
        if ($user->id !== $appointment->provider_id && $user->id !== $appointment->user_id) {
            return response()->json(['message' => 'You are not involved in this appointment'], 403);
        }

        // Determine who filed the complaint (user or provider)
        $reportedBy = $user->id === $appointment->provider_id ? 'provider' : 'user';

        // Get the complaint based on the appointment ID and who filed it
        $complaint = Complaint::with(['user', 'provider']) // eager load user (customer) and provider
            ->where('appointment_id', $appointmentId)
            ->where('reported_by', $reportedBy) // Filter by who filed it (provider or user)
            ->first();

        // If the complaint does not exist, return a message
        if (!$complaint) {
            return response()->json(['message' => 'No complaint filed for this appointment'], 404);
        }

        // Return the complaint details
        return response()->json([
            'complaint' => $complaint,
        ]);
    }

    public function changeComplaintStatus(Request $request, $complaintId)
    {
        // Validate incoming data
        $validated = $request->validate([
            'status' => 'required|in:pending,resolved',
        ]);

        // Find the complaint by ID
        $complaint = Complaint::find($complaintId);
        if (!$complaint) {
            return response()->json(['message' => 'Complaint not found'], 404);
        }

        // Update the status
        $complaint->status = $validated['status'];
        $complaint->save();

        // Return success response
        return response()->json([
            'message' => 'Complaint status updated successfully.',
            'complaint' => $complaint
        ]);
    }
}
