<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LawyerTitle;
use Illuminate\Http\Request;

class LawyerTitleController extends Controller
{
    public function index()
    {
        $lawyerTitles = LawyerTitle::all();
        return response()->json($lawyerTitles);
    }

    // Show a single lawyer type by ID
    public function show($id)
    {
        $lawyerTitle = LawyerTitle::find($id);
        if (!$lawyerTitle) {
            return response()->json(['message' => 'Lawyer Title not found'], 404);
        }
        return response()->json($lawyerTitle);
    }

    // Add a new lawyer type
    public function store(Request $request)
    {

        $request->validate([
            'title' => 'required|string|unique:lawyer_titles,title|max:255',
        ]);

        $lawyerTitle = LawyerTitle::create([
            'title' => $request->title,
        ]);

        return response()->json($lawyerTitle, 201); // 201 for resource creation
    }

    // Update an existing lawyer type
    public function update(Request $request, $id)
    {
        $lawyerTitle = LawyerTitle::find($id);

        if (!$lawyerTitle) {
            return response()->json(['message' => 'Lawyer Title not found'], 404);
        }

        $request->validate([
            'title' => 'required|string|unique:lawyer_titles,title,' . $id . '|max:255',
        ]);

        $lawyerTitle->update([
            'title' => $request->title,
        ]);

        return response()->json($lawyerTitle);
    }

    // Delete a lawyer type
    public function destroy($id)
    {
        $lawyerTitle = LawyerTitle::find($id);

        if (!$lawyerTitle) {
            return response()->json(['message' => 'Lawyer Title not found'], 404);
        }

        $lawyerTitle->delete();

        return response()->json(['message' => 'Lawyer Title deleted successfully']);
    }
}