<?php

namespace App\Http\Controllers\API\Document;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\PrivateDocument;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CommonNotification;
use Illuminate\Http\Request;
use ZipArchive;

class DocumentController extends Controller
{
    public function upload(Request $request)
    {


        // Validate the incoming request
        $validated = $request->validate([
            'document' => 'required|mimes:pdf,jpg,png,docx|max:2048', // Validate file type and size
            'type' => 'required|in:verification,public,private', // Document type
            // 'created_by' => 'required_if:type,private|nullable|exists:users,id',
            'created_for' => 'required_if:type,private|nullable|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',

        ]);

        // Get the authenticated user
        $user = $request->user();

        // Generate a unique file name
        $documentName = time() . '_' . $user->id . '_' . uniqid() . '.' . $request->document->getClientOriginalExtension();


        $request->document->move(public_path('images/documents'), $documentName);

        // Generate full URL
        $documentUrl = asset('images/documents/' . $documentName); // or asset('images/' . $documentName)

        // Create a new document record in the database
        $document = Document::create([
            'user_id' => $user->id,
            'document_link' => $documentUrl,
            'type' => $validated['type'],
        ]);

        if ($validated['type'] === 'private') {
            PrivateDocument::create([
                'document_id' => $document->id,
                'created_by' => $user->id,
                'created_for' => $validated['created_for'],
                'appointment_id' => $validated['appointment_id'] ?? null,
            ]);
        }


       $createdForUser = User::find($validated['created_for']);

        $createdForUser->notify(new CommonNotification($createdForUser,  "New Document Uploaded", "New document has been uploaded for you."));

        return response()->json(['message' => 'Document uploaded successfully', 'document' => $document], 201);
    }


    public function uploadMultiple(Request $request)
    {


        // Validate the incoming request for multiple files and related fields
        $validated = $request->validate([
            'document' => 'required|array',
            'document.*' => 'required|mimes:pdf,jpg,png,docx|max:2048', // each file
            'type' => 'required|in:verification,public,private', // Document type
            // For private, these fields are required for each document (if applicable)
            // 'created_by' => 'required_if:type,private|nullable|exists:users,id',
            'created_for' => 'required_if:type,private|nullable|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',
        ]);

        $user = $request->user();

        $uploadedDocuments = [];

        foreach ($request->file('document') as $file) {
            // Generate unique file name
            $documentName = time() . '_' . $user->id . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Move file to storage folder
            $file->move(public_path('images/documents'), $documentName);

            // Generate URL
            $documentUrl = asset('images/documents/' . $documentName);

            // Create document record
            $document = Document::create([
                'user_id' => $user->id,
                'document_link' => $documentUrl,
                'type' => $validated['type'],
            ]);

            // If private, create private_document record
            if ($validated['type'] === 'private') {
                PrivateDocument::create([
                    'document_id' => $document->id,
                    'created_by' => $user->id,
                    'created_for' => $validated['created_for'],
                    'appointment_id' => $validated['appointment_id'] ?? null,
                ]);
            }

            $uploadedDocuments[] = $document;
        }

        $createdForUser = User::find($validated['created_for']);

        $createdForUser->notify(new CommonNotification($createdForUser,  "New Document Uploaded", "New document has been uploaded for you."));

        return response()->json([
            'message' => 'Documents uploaded successfully',
            'documents' => $uploadedDocuments,
        ], 201);
    }


    public function getVerificationDocuments($uniqueId)
    {

        $user = User::findByUniqueUserId($uniqueId);

        // Get all verification documents for the user
        $documents = Document::where('user_id', $user->id)
            ->where('type', 'verification')
            ->get();

        return response()->json(['documents' => $documents]);
    }
    //
   public function getPrivateDocuments()
{
    $authUserId = auth()->id();

    $rows = \DB::table('private_documents as pd')
        ->join('documents as d', 'pd.document_id', '=', 'd.id')
        ->join('customer_profiles as cp', 'pd.created_for', '=', 'cp.user_id')
        ->join('users as u', 'cp.user_id', '=', 'u.id') // profile owner
        ->join('users as target_user', 'pd.created_for', '=', 'target_user.id') // name of created_for user
        ->leftJoin('appointments as ap', 'pd.appointment_id', '=', 'ap.id') // appointment details
        ->select(
            'u.id as user_id',
            'cp.gender',
            'cp.dob',
            'cp.avatar',
            'target_user.name as created_for_name',

            'd.id as document_id',
            'd.document_link',
            'd.type',

            'pd.created_at as private_doc_created_at',

            // appointment fields
            'ap.id as appointment_id',
            'ap.customer_id',
            'ap.provider_id',
            'ap.status as appointment_status',
            'ap.price',
            'ap.is_money_back',
            'ap.is_cancel_by_user',
            'ap.appointment_time',
            'ap.remarks',
            'ap.created_at as appointment_created_at',
            'ap.updated_at as appointment_updated_at'
        )
        ->where('pd.created_by', $authUserId)
        ->orderBy('u.id')
        ->get();

    $users = [];
    foreach ($rows as $row) {
        if (!isset($users[$row->user_id])) {
            $users[$row->user_id] = [
                'user_id' => $row->user_id,
                'gender' => $row->gender,
                'dob' => $row->dob,
                'avatar' => $row->avatar,
                'created_for_name' => $row->created_for_name,
                'documents_count' => 0,
                'documents' => []
            ];
        }

        $users[$row->user_id]['documents'][] = [
            'document_id' => $row->document_id,
            'document_link' => $row->document_link,
            'type' => $row->type,
            'created_at' => $row->private_doc_created_at,
            'appointment' => [
                'id' => $row->appointment_id,
                'customer_id' => $row->customer_id,
                'provider_id' => $row->provider_id,
                'status' => $row->appointment_status,
                'price' => $row->price,
                'is_money_back' => (bool) $row->is_money_back,
                'is_cancel_by_user' => (bool) $row->is_cancel_by_user,
                'appointment_time' => $row->appointment_time,
                'remarks' => $row->remarks,
                'created_at' => $row->appointment_created_at,
                'updated_at' => $row->appointment_updated_at
            ]
        ];

        $users[$row->user_id]['documents_count']++;
    }

    return response()->json([
        'shared_documents' => array_values($users)
    ]);
}




    //

    public function getPublicDocuments($uniqueId)
    {
        $user = User::findByUniqueUserId($uniqueId);
        // Get all verification documents for the user
        $documents = Document::where('user_id', $user->id)
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
        // Get all private documents created by the customer for the provider
        $customer = User::findByUniqueUserId($customerUniqueId);
        $provider = User::findByUniqueUserId($providerUniqueId);
        $documents = PrivateDocument::with('document')->where('created_by', $customer->id)
            ->where('created_for', $provider->id)
            ->get();

        return response()->json(['documents' => $documents]);
    }


    // public function listProviderMembers(Request $request, $roleId)
    // {
    //     $customerId = $request->user()->id;

    //     // Get user IDs of members who have private documents with customer (either direction)
    //     $memberIdsCreatedFor = PrivateDocument::where('created_by', $customerId)
    //         ->pluck('created_for')->toArray();

    //     $memberIdsCreatedBy = PrivateDocument::where('created_for', $customerId)
    //         ->pluck('created_by')->toArray();

    //     // Merge and unique, exclude the customer themselves
    //     $memberIds = collect(array_merge($memberIdsCreatedBy, $memberIdsCreatedFor))
    //         ->unique()
    //         ->filter(fn($id) => $id != $customerId)
    //         ->values();

    //     // Filter members by matching role ID
    //     $members = User::with('role') // eager load role here
    //         ->whereIn('id', $memberIds)
    //         ->where('role_id', $roleId)
    //         ->get();



    //     $members->each(function ($member) {
    //         $roleName = strtolower($member->role->name);

    //         if ($roleName === 'doctor') {
    //             $member->load(['doctorProfile', 'doctorProfile.doctorType', 'doctorProfile.doctorSpeciality', 'doctorProfile.doctorTitle']);
    //         } elseif ($roleName === 'lawyer') {
    //             $member->load(['lawyerProfile', 'lawyerProfile.lawyerSpeciality', 'lawyerProfile.lawyerTitle' ]);
    //         } else {

    //             $member->load(['commonProfile', 'commonProfile.uniqueIdentification', 'commonProfile.commonSpeciality']);
    //         }
    //     });



    //     return response()->json($members);
    // }


    public function listProviderMembers(Request $request, $roleId)
    {
        $customerId = $request->user()->id;

        // Get user IDs of members who have private documents with customer (either direction)
        $memberIdsCreatedFor = PrivateDocument::where('created_by', $customerId)
            ->pluck('created_for')->toArray();

        $memberIdsCreatedBy = PrivateDocument::where('created_for', $customerId)
            ->pluck('created_by')->toArray();

        // Merge and unique, exclude the customer themselves
        $memberIds = collect(array_merge($memberIdsCreatedBy, $memberIdsCreatedFor))
            ->unique()
            ->filter(fn($id) => $id != $customerId)
            ->values();

        // Fetch members and their appointments as provider
        $members = User::with([
            'role',
            'appointmentsAsProvider' => function ($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            }
        ])
            ->whereIn('id', $memberIds)
            ->where('role_id', $roleId)
            ->get();

        // Load additional profiles based on role
        $members->each(function ($member) {
            $roleName = strtolower($member->role->name);

            // Add appointments as provider
            $member->appointments = $member->appointmentsAsProvider;

            unset($member->appointmentsAsProvider);

            if ($roleName === 'doctor') {
                $member->load(['doctorProfile', 'doctorProfile.doctorType', 'doctorProfile.doctorSpeciality', 'doctorProfile.doctorTitle']);
            } elseif ($roleName === 'lawyer') {
                $member->load(['lawyerProfile', 'lawyerProfile.lawyerSpeciality', 'lawyerProfile.lawyerTitle']);
            } else {
                $member->load(['commonProfile', 'commonProfile.uniqueIdentification', 'commonProfile.commonSpeciality']);
            }
        });

        return response()->json($members);
    }


    public function getDocumentsBetweenUsers(Request $request, $providerUniqueId)
    {
        $customerId = $request->user()->id;

        $providerId = optional(User::findByUniqueUserId($providerUniqueId))->id;

        // Documents uploaded by customer for provider
        $providedByCustomer = Document::with('privateDocument')
            ->whereHas('privateDocument', function ($query) use ($customerId, $providerId) {
                $query->where('created_by', $customerId)
                    ->where('created_for', $providerId);
            })
            ->get();

        // Documents uploaded by provider for customer
        $providedByProvider = Document::with('privateDocument')
            ->whereHas('privateDocument', function ($query) use ($customerId, $providerId) {
                $query->where('created_by', $providerId)
                    ->where('created_for', $customerId);
            })
            ->get();

        return response()->json([
            'provided_by_you' => $providedByCustomer,
            'provided_by_provider' => $providedByProvider,
        ]);
    }



    public function downloadDocument($documentId)
    {
        $document = Document::findOrFail($documentId);

        // Extract relative path from URL stored in document_link
        $relativePath = str_replace(asset('/'), '', $document->document_link);

        $fullPath = public_path($relativePath);

        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return response()->download($fullPath, basename($fullPath));
    }


    public function downloadAllDocuments(Request $request, $providerId)
    {
        $customerId = $request->user()->id;

        // Get all document records related to the customer and provider (both directions)
        $documents = Document::whereHas('privateDocument', function ($query) use ($customerId, $providerId) {
            $query->where(function ($q) use ($customerId, $providerId) {
                $q->where('created_by', $customerId)
                    ->where('created_for', $providerId);
            })->orWhere(function ($q) use ($customerId, $providerId) {
                $q->where('created_by', $providerId)
                    ->where('created_for', $customerId);
            });
        })
            ->get();

        if ($documents->isEmpty()) {
            return response()->json(['message' => 'No documents found'], 404);
        }

        // Create a temp zip file
        $zipFileName = 'documents_' . time() . '.zip';
        $zipFilePath = storage_path('app/public/' . $zipFileName);

        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            foreach ($documents as $document) {
                // Extract filename from your stored URL, assuming it's like 'http://yourapp.com/images/documents/time_userid.ext'
                $documentUrl = $document->document_link;

                // Parse URL path and extract the filename
                $path = parse_url($documentUrl, PHP_URL_PATH);  // e.g. /images/documents/time_userid.ext
                $filename = basename($path);                    // e.g. time_userid.ext

                // Full path on server (public/images/documents/...)
                $fullPath = public_path('images/documents/' . $filename);

                if (file_exists($fullPath)) {
                    $zip->addFile($fullPath, $filename);
                }
            }
            $zip->close();
        } else {
            return response()->json(['message' => 'Failed to create ZIP file'], 500);
        }

        // Return ZIP as download response and delete file after sending
        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }
}
