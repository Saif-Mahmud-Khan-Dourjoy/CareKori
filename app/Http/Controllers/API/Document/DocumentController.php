<?php

namespace App\Http\Controllers\API\Document;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\PrivateDocument;
use App\Models\User;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function upload(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'document' => 'required|mimes:pdf,jpg,png,docx|max:2048', // Validate file type and size
            'type' => 'required|in:verification,public,private', // Document type

        ]);

        // Get the authenticated user
        $user = $request->user();

        // Generate a unique file name
        $documentName = time() . '_' . $user->id . '.' . $request->document->getClientOriginalExtension();


        $request->document->move(public_path('images/documents'), $documentName);

        // Generate full URL
        $documentUrl = asset('images/documents/' . $documentName); // or asset('images/' . $documentName)

        // Create a new document record in the database
        $document = Document::create([
            'user_id' => $user->id,
            'document_link' => $documentUrl,
            'type' => $validated['type'],
        ]);

        return response()->json(['message' => 'Document uploaded successfully', 'document' => $document], 201);
    }

    public function getVerificationDocuments($uniqueId)
    {
        // Get all verification documents for the user
        $documents = Document::where('unique_user_id', $uniqueId)
            ->where('type', 'verification')
            ->get();

        return response()->json(['documents' => $documents]);
    }

    public function getPublicDocuments($uniqueId)
    {
        // Get all verification documents for the user
        $documents = Document::where('unique_user_id', $uniqueId)
            ->where('type', 'public')
            ->get();

        return response()->json(['documents' => $documents]);
    }

    public function getDocumentsByAppointment($id)
    {
        // Get all private documents linked to an appointment
        $documents = PrivateDocument::with('document')->where('appointment_id', $id)->get();

        return response()->json(['documents' => $documents]);
    }

    public function getPrivateDocumentsForCustomer($customerUniqueId, $providerUniqueId)
    {
        // Get all private documents created for the customer by the provider
        $customer = User::findByUniqueUserId($customerUniqueId);
        $provider = User::findByUniqueUserId($providerUniqueId);
        $documents = PrivateDocument::with('document')->where('created_for', $customer->id)
            ->where('created_by', $provider->id)
            ->get();

        return response()->json(['documents' => $documents]);
    }

    public function getPrivateDocumentsForProvider($providerUniqueId, $customerUniqueId)
    {
        // Get all private documents created by the provider for the customer
        $customer = User::findByUniqueUserId($customerUniqueId);
        $provider = User::findByUniqueUserId($providerUniqueId);
        $documents = PrivateDocument::where('created_by', $provider->id)
            ->where('created_for', $customer->id)
            ->get();

        return response()->json(['documents' => $documents]);
    }
}
