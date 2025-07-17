<?php

namespace App\Http\Controllers;

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
        ]);

        // Ensure the provider exists
        $provider = User::where('unique_user_id', $validated['provider_unique_user_id'])->first();
        if (!$provider) {
            return response()->json(['message' => 'Provider not found'], 404);
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
            'user_id' => Auth::id(), // The logged-in user's ID
            'provider_id' => $provider->id,
            'complaint_text' => $validated['complaint_text'],
            'is_rude' => $isRude, // Store as a boolean
            'is_late' => $isLate, // Store as a boolean
            'interrupted' => $interrupted, // Store as a boolean
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
        $complaints = Complaint::with(['provider', 'user'])->get();

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
        $complaints = Complaint::with(['user'])->where('provider_id', $provider->id)->get();

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
        $complaints = Complaint::with(['provider'])->where('user_id', Auth::id())
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
}