<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AddBanner;
use Illuminate\Http\Request;

class AddBannerController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'add_image' => 'required|mimes:jpg,png,jpeg|max:2048',
            'add_for' => 'nullable|string',
            'add_type' => 'nullable|string',
        ]);

        $addName = time() . '_'  . $request->add_image->getClientOriginalExtension();


        $request->add_image->move(public_path('images/add'), $addName);

        // Generate full URL
        $addUrl = asset('images/add/' . $addName);

        // Create a new banner
        $banner = AddBanner::create([
            'add_image' => $addUrl,
            'add_for' => $validated['add_for'] ?? null,
            'add_type' => $validated['add_type'] ?? null,
        ]);

        return response()->json([
            'message' => 'Banner created successfully.',
            'banner' => $banner,
        ], 201);
    }

    // Get the latest banner
    public function getLatest()
    {
        // Get the latest banner (the most recently added one)
        $latestBanner = AddBanner::latest()->first();

        if (!$latestBanner) {
            return response()->json(['message' => 'No banners found'], 404);
        }

        return response()->json([
            'banner' => $latestBanner,
        ]);
    }

    // Get all banners
    public function getAll()
    {
        // Get all banners
        $banners = AddBanner::all();

        if ($banners->isEmpty()) {
            return response()->json(['message' => 'No banners found'], 404);
        }

        return response()->json([
            'banners' => $banners,
        ]);
    }
}