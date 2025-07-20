<?php

namespace App\Http\Controllers\API\Review;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'service_provider_id' => 'required|exists:users,id',
    //         'review' => 'nullable|string',
    //         'rating' => 'required|numeric|min:0|max:5',
    //     ]);

    //     if (auth()->user()->role->name !== 'customer') {
    //         return response()->json(['error' => 'Only customers can post reviews'], 403);
    //     }

    //     // Check if the user has an appointment with the provider and that it is completed
    //     $appointment = Appointment::where('customer_id', auth()->id())
    //         ->where('provider_id', $request->service_provider_id)
    //         ->where('status', 'completed')
    //         ->first();

    //     if (!$appointment) {
    //         return response()->json(['error' => 'You can only review providers after a completed appointment'], 403);
    //     }

    //     $review = Review::create([
    //         'customer_id' => auth()->id(),
    //         'service_provider_id' => $request->service_provider_id,
    //         'review' => $request->review,
    //         'rating' => $request->rating,
    //     ]);

    //     return response()->json(['message' => 'Review submitted for approval', 'review' => $review], 201);
    // }


    public function store(Request $request)
    {
        
        $request->validate([
            'service_provider_id' => 'required|exists:users,id',
            'review' => 'nullable|string',
            'rating' => 'required|numeric|min:0|max:5',
        ]);

       
        if (auth()->user()->role->name !== 'customer') {
            return response()->json(['error' => 'Only customers can post reviews'], 403);
        }

       
        $appointments = Appointment::where('customer_id', auth()->id())
            ->where('provider_id', $request->service_provider_id)
            ->whereIn('status', ['completed', 'confirmed'])
            ->get();

            if (count($appointments) <= 0) {
                return response()->json(['error' => 'You are not allowed to provide a review for this provider'], 403);
            }    

       
        $isReviewable = false;

        foreach ($appointments as $appointment) {
            
            $appointmentStartTime = Carbon::parse($appointment->appointment_time,'UTC');
            

            
            $currentTime = Carbon::now('UTC');

          
            if ($currentTime->diffInHours($appointmentStartTime, false) <= 2 ) {
                $isReviewable = true;
                break; 
            }
        }

        if (!$isReviewable) {
            return response()->json(['error' => 'You can only review providers within 2 hours of the appointment start or 1 hour after completion'], 403);
        }

       
        $review = Review::create([
            'customer_id' => auth()->id(),
            'service_provider_id' => $request->service_provider_id,
            'review' => $request->review,
            'rating' => $request->rating,
        ]);

        return response()->json(['message' => 'Review submitted for approval', 'review' => $review], 201);
    }







    // Admin or moderator approves/rejects
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $user = auth()->user();
        if (!in_array($user->role->name, ['super admin', 'moderator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review = Review::findOrFail($id);
        $review->status = $request->status;
        $review->save();

        return response()->json(['message' => 'Review status updated', 'review' => $review]);
    }

    // Get approved reviews for a service provider
    // public function getApprovedReviews($serviceProviderId)
    // {


    //     $reviews = Review::where('service_provider_id', $serviceProviderId)
    //         ->whereRaw('LOWER(status) LIKE ?', ['%approve%'])
    //         ->with('customer.customerProfile') // Eager load customer and their profile
    //         ->latest()
    //         ->get();

    //     return response()->json($reviews);
    // }

    public function getApprovedReviews($serviceProviderId)
    {
        $reviews = Review::whereHas('serviceProvider', function ($query) use ($serviceProviderId) {
            $query->where('unique_user_id', $serviceProviderId);
        })
            ->whereRaw('LOWER(status) LIKE ?', ['%approve%'])
            ->with([
                'customer.customerProfile',
                'serviceProvider'           // Also eager load the service provider if needed
            ])
            ->latest()
            ->get();

        return response()->json($reviews);
    }
}