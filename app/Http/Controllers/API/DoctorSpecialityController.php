<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DoctorSpeciality;
use Illuminate\Http\Request;

class DoctorSpecialityController extends Controller
{
    public function index()
    {
        $doctorSpecialities = DoctorSpeciality::all();
        return response()->json([   'success' => true, 'data' => $doctorSpecialities], 200);
    }

    // Show a single doctor type by ID
    public function show($id)
    {
        $doctorSpeciality = DoctorSpeciality::find($id);
        if (!$doctorSpeciality) {
            return response()->json(['message' => 'Doctor Speciality not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $doctorSpeciality], 200); // 200 for successful retrieval
    }

    // Add a new doctor type
    public function store(Request $request)
    {

        $request->validate([
            'specialized_at' => 'required|string|unique:doctor_specialities,specialized_at|max:255',
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

        $doctorSpeciality = DoctorSpeciality::create([
            'specialized_at' => $request->specialized_at,
            'icon' => $iconPath,
        ]);

        return response()->json(['success' => true, 'data' => $doctorSpeciality], 201); // 201 for resource creation
    }

    // Update an existing doctor type
    public function update(Request $request, $id)
    {
        $doctorSpeciality = DoctorSpeciality::find($id);

        if (!$doctorSpeciality) {
            return response()->json(['message' => 'Doctor Speciality not found'], 404);
        }

        $request->validate([
            'specialized_at' => 'required|string|unique:doctor_specialities,specialized_at,' . $id . '|max:255',
            'icon' => 'nullable|mimes:jpeg,jpg,png|max:2048',
        ]);
        
        $iconPath = null;
        if ($doctorSpeciality->icon) {
            $iconPath = $doctorSpeciality->icon;
        }
        if ($request->hasFile('icon') && $request->file('icon')->isValid()) {

            // Optional: Delete old icon if exists
            if ($iconPath) {
                $baseUrl = asset('');
                $relativePath = str_replace($baseUrl, '', $iconPath);
                $absolutePath = public_path($relativePath);
                if (file_exists($absolutePath)) {
                    unlink($absolutePath);
                }
            }

            // Save new icon
            $iconFile = $request->file('icon');
            $filename =  time() . '.' . $iconFile->getClientOriginalExtension();
            $iconFile->move(public_path('images/icons/role/speciality'), $filename);

            // Generate full URL
            $iconPath = asset('images/icons/role/speciality/' . $filename); // or asset('images/' . $documentName)
        }

        $doctorSpeciality->update([
            'specialized_at' => $request->specialized_at,
            'icon' => $iconPath,
        ]);

        return response()->json(['success' => true, 'data' => $doctorSpeciality], 200); // 200 for successful update
    }

    // Delete a doctor type
    public function destroy($id)
    {
        $doctorSpeciality = DoctorSpeciality::find($id);

        if (!$doctorSpeciality) {
            return response()->json(['message' => 'Doctor Speciality not found'], 404);
        }

        // Optional: Delete icon if exists
        if ($doctorSpeciality->icon) {
            $baseUrl = asset('');
            $relativePath = str_replace($baseUrl, '', $doctorSpeciality->icon);
            $absolutePath = public_path($relativePath);
            if (file_exists($absolutePath)) {
                unlink($absolutePath);
            }
        }

        $doctorSpeciality->delete();

        return response()->json(['message' => 'Doctor Speciality deleted successfully']);
    }
}