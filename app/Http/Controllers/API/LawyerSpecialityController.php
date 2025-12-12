<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LawyerSpeciality;
use Illuminate\Http\Request;

class LawyerSpecialityController extends Controller
{
    public function index()
    {
        $lawyerSpecialities = LawyerSpeciality::all();
        return response()->json(['success' => true, 'data' => $lawyerSpecialities], 200);
    }

    // Show a single doctor type by ID
    public function show($id)
    {
        $lawyerSpeciality = LawyerSpeciality::find($id);
        if (!$lawyerSpeciality) {
            return response()->json(['message' => 'Doctor Speciality not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $lawyerSpeciality], 200); // 200 for successful retrieval
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

        $lawyerSpeciality = LawyerSpeciality::create([
            'specialized_at' => $request->specialized_at,
            'icon' => $iconPath,
        ]);

        return response()->json(['success' => true, 'data' => $lawyerSpeciality], 201); // 201 for resource creation
    }

    // Update an existing doctor type
    public function update(Request $request, $id)
    {
        $lawyerSpeciality = LawyerSpeciality::find($id);

        if (!$lawyerSpeciality) {
            return response()->json(['message' => 'Lawyer Speciality not found'], 404);
        }

        $request->validate([
            'specialized_at' => 'required|string|unique:lawyer_specialities,specialized_at,' . $id . '|max:255',
            'icon' => 'nullable|mimes:jpeg,jpg,png|max:2048',
        ]);


        $iconPath = null;
        if ($lawyerSpeciality->icon) {
            $iconPath = $lawyerSpeciality->icon;
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
        

        $lawyerSpeciality->update([
            'specialized_at' => $request->specialized_at,
            'icon' => $iconPath,
        ]);

        return response()->json(['success' => true, 'data' => $lawyerSpeciality], 200); // 200 for successful update
    }

    // Delete a doctor type
    public function destroy($id)
    {
        $lawyerSpeciality = LawyerSpeciality::find($id);

        if (!$lawyerSpeciality) {
            return response()->json(['message' => 'Doctor Speciality not found'], 404);
        }

        // Optional: Delete icon if exists
        if ($lawyerSpeciality->icon) {
            $baseUrl = asset('');
            $relativePath = str_replace($baseUrl, '', $lawyerSpeciality->icon);
            $absolutePath = public_path($relativePath);
            if (file_exists($absolutePath)) {
                unlink($absolutePath);
            }
        }

        $lawyerSpeciality->delete();

        return response()->json(['message' => 'Doctor Speciality deleted successfully']);
    }
}