<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CommonProviderSpeciality extends Controller
{
    public function addCommonProviderSpeciality(Request $request)
    {
        $validatedData = $request->validate([
            'category_id' => 'required|exists:roles,id',
            'specialized_at' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|mimes:jpeg,jpg,png|max:2048',
        ]);

        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconFile = $request->file('icon');
            $filename =  time() . '.' . $iconFile->getClientOriginalExtension();
            $iconFile->move(public_path('images/icons/role/speciality'), $filename);

            // Generate full URL
            $iconPath = asset('images/icons/role/speciality/' . $filename); // or asset('images/' . $documentName)

        }

        $commonProviderSpeciality = new \App\Models\CommonProviderSpeciality();
        $commonProviderSpeciality->category_id = $validatedData['category_id'];
        $commonProviderSpeciality->specialized_at = $validatedData['specialized_at'];
        $commonProviderSpeciality->description = $validatedData['description']?? null;
        $commonProviderSpeciality->icon = $iconPath;
        $commonProviderSpeciality->created_at = now();
        $commonProviderSpeciality->updated_at = now();
        $commonProviderSpeciality->save();
        return response()->json([
            'message' => 'Common Provider Speciality added successfully',
            'data' => $commonProviderSpeciality,
        ], 201);
    }

    public function getCommonProviderSpecialitiesByRoleId($roleId)
    {
        $commonProviderSpecialities = \App\Models\CommonProviderSpeciality::where('category_id', $roleId)->get();
        return response()->json([
            'message' => 'Common Provider Specialities retrieved successfully',
            'data' => $commonProviderSpecialities,
        ], 200);
    }
    
    public function updateCommonProviderSpeciality(Request $request, $id)
    {
        $validatedData = $request->validate([
            'category_id' => 'required|exists:roles,id',
            'specialized_at' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|mimes:jpeg,jpg,png|max:2048',
        ]);

        $commonProviderSpeciality = \App\Models\CommonProviderSpeciality::find($id);
        if (!$commonProviderSpeciality) {
            return response()->json([
                'message' => 'Common Provider Speciality not found',
            ], 404);
        }

        if ($request->hasFile('icon')) {
            
            if($commonProviderSpeciality->icon) {
                // Optional: Delete old icon if exists
                $baseUrl = asset('');
                $oldIconPath = str_replace($baseUrl, '', $commonProviderSpeciality->icon);
                if (file_exists(public_path($oldIconPath))) {
                    unlink(public_path($oldIconPath));
                }
            }
            $iconFile = $request->file('icon');
            $filename =  time() . '.' . $iconFile->getClientOriginalExtension();
            $iconFile->move(public_path('images/icons/role/speciality'), $filename);

            // Generate full URL
            $iconPath = asset('images/icons/role/speciality/' . $filename); 
            $commonProviderSpeciality->icon = $iconPath;
        }

        $commonProviderSpeciality->category_id = $validatedData['category_id'];
        $commonProviderSpeciality->specialized_at = $validatedData['specialized_at'];
        $commonProviderSpeciality->description = $validatedData['description']?? null;
        $commonProviderSpeciality->updated_at = now();
        $commonProviderSpeciality->save();
        return response()->json([
            'message' => 'Common Provider Speciality updated successfully',
            'data' => $commonProviderSpeciality,
        ], 200);    
    }

    public function deleteCommonProviderSpeciality($id)
    {
        $commonProviderSpeciality = \App\Models\CommonProviderSpeciality::find($id);
        if (!$commonProviderSpeciality) {
            return response()->json([
                'message' => 'Common Provider Speciality not found',
            ], 404);
        }

        // Optional: Delete icon if exists
        if ($commonProviderSpeciality->icon) {
            $baseUrl = asset('');
            $oldIconPath = str_replace($baseUrl, '', $commonProviderSpeciality->icon);
            if (file_exists(public_path($oldIconPath))) {
                unlink(public_path($oldIconPath));
            }
        }

        $commonProviderSpeciality->delete();
        return response()->json([
            'message' => 'Common Provider Speciality deleted successfully',
        ], 200);
    }
    public function getCommonProviderSpecialityById($id)
    {
        $commonProviderSpeciality = \App\Models\CommonProviderSpeciality::find($id);
        if (!$commonProviderSpeciality) {
            return response()->json([
                'message' => 'Common Provider Speciality not found',
            ], 404);
        }
        return response()->json([
            'message' => 'Common Provider Speciality retrieved successfully',
            'data' => $commonProviderSpeciality,
        ], 200);
    }
    
  
   
}