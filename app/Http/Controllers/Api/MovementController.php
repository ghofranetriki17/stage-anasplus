<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use Illuminate\Http\Request;

class MovementController extends Controller
{
    public function index()
    {
        $movements = Movement::with('exercises')->get();//yomkn nhotha ka amthila ll les exercuces yomkn nahiha
        return response()->json($movements);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url|max:255',
        ]);

        $movement = Movement::create($validated);
        return response()->json($movement, 201);
    }

    public function show(Movement $movement)
    {
        return response()->json($movement->load('exercises'));
    }

    public function update(Request $request, Movement $movement)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url|max:255',
        ]);

        $movement->update($validated);
        return response()->json($movement);
    }

    public function destroy(Movement $movement)
    {
        $movement->delete();
        return response()->json(['message' => 'Movement deleted successfully']);
    }
}