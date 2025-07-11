<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\Machine;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    public function index()
    {
        $exercises = Exercise::with(['movement', 'machine', 'charge'])->get();
        return response()->json($exercises);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'movement_id' => 'nullable|exists:movements,id',
            'machine_id' => 'nullable|exists:machines,id',
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'sets' => 'required|integer|min:1',
            'reps' => 'required|integer|min:1',
            'charge_id' => 'nullable|exists:charges,id',
            'instructions' => 'nullable|string',
        ]);

        // Validate that charge belongs to selected machine
        if ($validated['machine_id'] && $validated['charge_id']) {
            $machine = Machine::with('charges')->find($validated['machine_id']);
            if (!$machine->charges->contains($validated['charge_id'])) {
                return response()->json(['error' => 'Charge does not belong to selected machine'], 400);
            }
        }

        $exercise = Exercise::create($validated);
        return response()->json($exercise->load(['movement', 'machine', 'charge']), 201);
    }

    public function show(Exercise $exercise)
    {
        return response()->json($exercise->load(['movement', 'machine', 'charge']));
    }

    public function update(Request $request, Exercise $exercise)
    {
        $validated = $request->validate([
            'movement_id' => 'nullable|exists:movements,id',
            'machine_id' => 'nullable|exists:machines,id',
            'name' => 'sometimes|required|string|max:255',
            'title' => 'nullable|string|max:255',
            'sets' => 'sometimes|required|integer|min:1',
            'reps' => 'sometimes|required|integer|min:1',
            'charge_id' => 'nullable|exists:charges,id',
            'instructions' => 'nullable|string',
        ]);

        // Validate that charge belongs to selected machine
        if (isset($validated['machine_id']) && isset($validated['charge_id'])) {
            $machine = Machine::with('charges')->find($validated['machine_id']);
            if (!$machine->charges->contains($validated['charge_id'])) {
                return response()->json(['error' => 'Charge does not belong to selected machine'], 400);
            }
        }

        $exercise->update($validated);
        return response()->json($exercise->load(['movement', 'machine', 'charge']));
    }

    public function destroy(Exercise $exercise)
    {
        $exercise->delete();
        return response()->json(['message' => 'Exercise deleted successfully']);
    }

    public function getChargesForMachine($machineId)
    {
        $machine = Machine::with('charges')->find($machineId);
        if (!$machine) {
            return response()->json(['error' => 'Machine not found'], 404);
        }
        return response()->json($machine->charges);
    }
}