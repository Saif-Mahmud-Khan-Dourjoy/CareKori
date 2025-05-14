<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DoctorTitle as ModelsDoctorTitle;

class DoctorTitle extends Controller
{
    public function index()
    {
        $doctorTitles = ModelsDoctorTitle::all();
        return response()->json(['success' => true, 'data' => $doctorTitles],   200);
    }

    // Show a single doctor type by ID
    public function show($id)
    {
        $doctorTitle = ModelsDoctorTitle::find($id);
        if (!$doctorTitle) {
            return response()->json(['message' => 'Doctor Title not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $doctorTitle], 200); // 200 for successful retrieval
    }

    // Add a new doctor type
    public function store(Request $request)
    {

        $request->validate([
            'title' => 'required|string|unique:doctor_titles,title|max:255',
        ]);

        $doctorTitle = ModelsDoctorTitle::create([
            'title' => $request->title,
        ]);

        return response()->json(['success' => true, 'data' => $doctorTitle],201); // 201 for resource creation
    }

    // Update an existing doctor type
    public function update(Request $request, $id)
    {
        $doctorTitle = ModelsDoctorTitle::find($id);

        if (!$doctorTitle) {
            return response()->json(['message' => 'Doctor Title not found'], 404);
        }

        $request->validate([
            'title' => 'required|string|unique:doctor_titles,title,' . $id . '|max:255',
        ]);

        $doctorTitle->update([
            'title' => $request->title,
        ]);

        return response()->json(['success' => true, 'data' => $doctorTitle], 200); // 200 for successful update
    }

    // Delete a doctor type
    public function destroy($id)
    {
        $doctorTitle = ModelsDoctorTitle::find($id);

        if (!$doctorTitle) {
            return response()->json(['message' => 'Doctor Title not found'], 404);
        }

        $doctorTitle->delete();

        return response()->json(['message' => 'Doctor Title deleted successfully']);
    }
}