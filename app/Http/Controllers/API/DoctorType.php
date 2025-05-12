<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DoctorType as ModelsDoctorType;
use Illuminate\Http\Request;

class DoctorType extends Controller
{
    public function index()
    {
        $doctorTypes = ModelsDoctorType::all();
        return response()->json($doctorTypes);
    }

    // Show a single doctor type by ID
    public function show($id)
    {
        $doctorType = ModelsDoctorType::find($id);
        if (!$doctorType) {
            return response()->json(['message' => 'Doctor Type not found'], 404);
        }
        return response()->json($doctorType);
    }

    // Add a new doctor type
    public function store(Request $request)
    {
       
        $request->validate([
            'type' => 'required|string|unique:doctor_types,type|max:255',
        ]);

        $doctorType = ModelsDoctorType::create([
            'type' => $request->type,
        ]);

        return response()->json($doctorType, 201); // 201 for resource creation
    }

    // Update an existing doctor type
    public function update(Request $request, $id)
    {
        $doctorType = ModelsDoctorType::find($id);

        if (!$doctorType) {
            return response()->json(['message' => 'Doctor Type not found'], 404);
        }

        $request->validate([
            'type' => 'required|string|unique:doctor_types,type,' . $id . '|max:255',
        ]);

        $doctorType->update([
            'type' => $request->type,
        ]);

        return response()->json($doctorType);
    }

    // Delete a doctor type
    public function destroy($id)
    {
        $doctorType = ModelsDoctorType::find($id);

        if (!$doctorType) {
            return response()->json(['message' => 'Doctor Type not found'], 404);
        }

        $doctorType->delete();

        return response()->json(['message' => 'Doctor Type deleted successfully']);
    }
}