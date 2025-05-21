<?php

namespace App\Http\Controllers\API\Review;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'service_provider_id' => 'required|exists:users,id',
            'review' => 'nullable|string',
            'rating' => 'required|numeric|min:0|max:5',
        ]);

        if (auth()->user()->role !== 'customer') {
            return response()->json(['error' => 'Only customers can post reviews'], 403);
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
        if (!in_array($user->role, ['super_admin', 'moderator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review = Review::findOrFail($id);
        $review->status = $request->status;
        $review->save();

        return response()->json(['message' => 'Review status updated', 'review' => $review]);
    }

    // Get approved reviews for a service provider
    public function getApprovedReviews($serviceProviderId)
    {
        $reviews = Review::where('service_provider_id', $serviceProviderId)
            ->where('status', 'approved')
            ->latest()
            ->get();

        return response()->json($reviews);
    }
}