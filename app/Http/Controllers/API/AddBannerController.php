<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AddBanner;
use App\Models\BannerCategory;
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

        $addName = time() . '.'  . $request->add_image->getClientOriginalExtension();


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
        // Get all banners with role information
        $banners = AddBanner::with('role')->get()->map(function ($banner) {
            return [
                'id' => $banner->id,
                'role_id' => $banner->role_id,
                'add_image' => $banner->add_image,
                'add_for' => $banner->add_for,
                'add_type' => $banner->add_type,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
                'role_name' => $banner->role ? $banner->role->name : null,
                'role' => $banner->role,
            ];
        });

        if ($banners->isEmpty()) {
            return response()->json(['message' => 'No banners found'], 404);
        }

        return response()->json([
            'banners' => $banners,
        ]);
    }

    // Delete a banner
    public function delete($id)
    {
        $banner = AddBanner::find($id);

        if (!$banner) {
            return response()->json(['message' => 'Banner not found'], 404);
        }

        // Delete the image file from storage
        $imagePath = str_replace(asset(''), '', $banner->add_image);
        $fullPath = public_path($imagePath);
        
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $banner->delete();

        return response()->json([
            'message' => 'Banner deleted successfully',
        ], 200);
    }



    public function storeByRole(Request $request, $roleId)
    {
        $validated = $request->validate([
            'add_image' => 'required|mimes:jpg,png,jpeg|max:2048',
            'add_for' => 'nullable|string',
            'add_type' => 'nullable|string',
        ]);

        $addName = time() . '.'  . $request->add_image->getClientOriginalExtension();


        $request->add_image->move(public_path('images/add'), $addName);

        // Generate full URL
        $addUrl = asset('images/add/' . $addName);

        $banner = AddBanner::create([
            'add_image' => $addUrl,
            'add_for' => $validated['add_for'] ?? null,
            'add_type' => $validated['add_type'] ?? null,
            'role_id' => $roleId,
        ]);

        return response()->json(['message' => 'Banner added successfully!', 'data' => $banner], 201);
    }

    // 2️⃣. GET the Latest Banner for a specific Role
    public function getLatestByRole($roleId)
    {
        $banner = AddBanner::with('role')->where('role_id', $roleId)
            ->latest()
            ->first();

        if ($banner) {
            $data = [
                'id' => $banner->id,
                'role_id' => $banner->role_id,
                'add_image' => $banner->add_image,
                'add_for' => $banner->add_for,
                'add_type' => $banner->add_type,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
                'role_name' => $banner->role ? $banner->role->name : null,
                'role' => $banner->role,
            ];
            return response()->json(['data' => $data]);
        } else {
            return response()->json(['message' => 'No banners found for this role'], 404);
        }
    }

    // 3️⃣. GET All Banners for a specific Role
    public function getAllByRole($roleId)
    {
        $banners = AddBanner::with('role')->where('role_id', $roleId)->get()->map(function ($banner) {
            return [
                'id' => $banner->id,
                'role_id' => $banner->role_id,
                'add_image' => $banner->add_image,
                'add_for' => $banner->add_for,
                'add_type' => $banner->add_type,
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
                'role_name' => $banner->role ? $banner->role->name : null,
                'role' => $banner->role,
            ];
        });

        return response()->json(['data' => $banners]);
    }
}