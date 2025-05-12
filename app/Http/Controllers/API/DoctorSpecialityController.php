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
        return response()->json($doctorSpecialities);
    }

    // Show a single doctor type by ID
    public function show($id)
    {
        $doctorSpeciality = DoctorSpeciality::find($id);
        if (!$doctorSpeciality) {
            return response()->json(['message' => 'Doctor Speciality not found'], 404);
        }
        return response()->json($doctorSpeciality);
    }

    // Add a new doctor type
    public function store(Request $request)
    {

        $request->validate([
            'specialized_at' => 'required|string|unique:doctor_specialities,specialized_at|max:255',
        ]);

        $doctorSpeciality = DoctorSpeciality::create([
            'specialized_at' => $request->specialized_at,
        ]);

        return response()->json($doctorSpeciality, 201); // 201 for resource creation
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
        ]);

        $doctorSpeciality->update([
            'specialized_at' => $request->specialized_at,
        ]);

        return response()->json($doctorSpeciality);
    }

    // Delete a doctor type
    public function destroy($id)
    {
        $doctorSpeciality = DoctorSpeciality::find($id);

        if (!$doctorSpeciality) {
            return response()->json(['message' => 'Doctor Speciality not found'], 404);
        }

        $doctorSpeciality->delete();

        return response()->json(['message' => 'Doctor Speciality deleted successfully']);
    }
}