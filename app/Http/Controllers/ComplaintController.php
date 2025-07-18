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

        if ($appointment->customer_id !== Auth::id()) {
            return response()->json(['message' => 'You can only complain about your own appointments'], 403);
        }

        // Convert the boolean values to actual booleans
        $isRude = filter_var($validated['is_rude'], FILTER_VALIDATE_BOOLEAN);
        $isLate = filter_var($validated['is_late'], FILTER_VALIDATE_BOOLEAN);
        $interrupted = filter_var($validated['interrupted'], FILTER_VALIDATE_BOOLEAN);

        // Ensure the provider is different from the logged-in user
        if (Auth::id() === (int) $provider->id) {
            return response()->json(['message' => 'You cannot complain about yourself'], 400);
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
        $complaints = Complaint::with(['provider', 'user', 'appointment'])->get();

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
    public function getComplaintsForAppointment($appointmentId)
    {
        // Ensure the appointment exists
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        // Eager load the 'user' and 'provider' relationships with the complaints
        $complaints = $appointment->complaints()
            ->with(['user', 'provider']) // Eager load both user and provider
            ->get();

        // If no complaints exist, return an appropriate message
        if ($complaints->isEmpty()) {
            return response()->json(['message' => 'No complaints found for this appointment'], 404);
        }

        // Return the complaints and appointment details
        return response()->json([
            'appointment' => $appointment,
            'complaints' => $complaints
        ]);
    }
}