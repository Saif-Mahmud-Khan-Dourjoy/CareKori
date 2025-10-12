<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LanguageState;
use App\Models\User;
use Illuminate\Http\Request;

class LanguageStateController extends Controller
{
    public function getLanguageState()
    {


        $languageState = LanguageState::where('user_id', auth()->user()->id)->first();

        if (!$languageState) {
            return response()->json([
                'message' => 'Language state not found.',
                'status' => false,
                'code' => 404,
            ], 404);
        }

        return response()->json([
            'message' => 'Language state retrieved successfully.',
            'data' => $languageState,
            'status' => true,
            'code' => 200,
        ]);
    }

    public function createOrUpdateLanguageState(Request $request)
    {
        $validated = $request->validate([

            'state'   => 'nullable|string|in:en,bn',
        ]);

        $languageState = LanguageState::updateOrCreate(
            ['user_id' => auth()->user()->id],
            ['state' => $validated['state'] ?? null]
        );

        return response()->json([
            'message' => 'Language state saved successfully.',
            'data' => $languageState,
            'status' => true,
            'code' => 200,
        ]);
    }
}
