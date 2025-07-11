<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workout;
use App\Models\Exercise;
use Illuminate\Http\Request;

class WorkoutController extends Controller
{
    public function index()
    {
        $workouts = Workout::with(['user', 'exercises'])->get();
        return response()->json($workouts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'water_consumption' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:0',
            'is_rest_day' => 'boolean',
            'exercises' => 'array',
            'exercises.*.id' => 'required|exists:exercises,id',
            'exercises.*.achievement' => 'nullable|numeric|min:0|max:100',
            'exercises.*.is_done' => 'boolean',
            'exercises.*.order' => 'nullable|integer|min:0',
        ]);

        $workout = Workout::create($validated);

        // Attach exercises with pivot data
        if (isset($validated['exercises'])) {
            foreach ($validated['exercises'] as $exerciseData) {
                $workout->exercises()->attach($exerciseData['id'], [
                    'achievement' => $exerciseData['achievement'] ?? 0,
                    'is_done' => $exerciseData['is_done'] ?? false,
                    'order' => $exerciseData['order'] ?? 0,
                ]);
            }
        }

        return response()->json($workout->load(['user', 'exercises']), 201);
    }

    public function show(Workout $workout)
    {
        return response()->json($workout->load(['user', 'exercises']));
    }

    public function update(Request $request, Workout $workout)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'water_consumption' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:0',
            'is_rest_day' => 'boolean',
        ]);

        $workout->update($validated);
        return response()->json($workout->load(['user', 'exercises']));
    }

    public function destroy(Workout $workout)
    {
        $workout->delete();
        return response()->json(['message' => 'Workout deleted successfully']);
    }

    public function updateExerciseProgress(Request $request, Workout $workout, Exercise $exercise)
    {
        $validated = $request->validate([
            'achievement' => 'nullable|numeric|min:0|max:100',
            'is_done' => 'boolean',
        ]);

        $workout->exercises()->updateExistingPivot($exercise->id, $validated);
        return response()->json(['message' => 'Exercise progress updated successfully']);
    }

    public function getExercises(Workout $workout)
    {
        $exercises = $workout->exercises()->with(['movement', 'machine', 'charge'])->get();
        return response()->json($exercises);
    }

    public function addExercise(Request $request, Workout $workout)
    {
        $validated = $request->validate([
            'exercise_id' => 'required|exists:exercises,id',
            'achievement' => 'nullable|numeric|min:0|max:100',
            'is_done' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        // Check if exercise is already in the workout
        if ($workout->exercises()->where('exercise_id', $validated['exercise_id'])->exists()) {
            return response()->json(['error' => 'Exercise already exists in this workout'], 400);
        }

        $workout->exercises()->attach($validated['exercise_id'], [
            'achievement' => $validated['achievement'] ?? 0,
            'is_done' => $validated['is_done'] ?? false,
            'order' => $validated['order'] ?? 0,
        ]);

        $exercise = Exercise::with(['movement', 'machine', 'charge'])->find($validated['exercise_id']);
        return response()->json($exercise, 201);
    }

    public function removeExercise(Workout $workout, Exercise $exercise)
    {
        if (!$workout->exercises()->where('exercise_id', $exercise->id)->exists()) {
            return response()->json(['error' => 'Exercise not found in this workout'], 404);
        }

        $workout->exercises()->detach($exercise->id);
        return response()->json(['message' => 'Exercise removed from workout successfully']);
    }
}