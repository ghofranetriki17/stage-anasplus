<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workout;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkoutController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $workouts = Workout::where('user_id', $user->id)
                ->with([
                    'user', 
                    'exercises' => function($query) {
                        $query->with(['movement', 'machine', 'charge']);
                    }
                ])
                ->orderByDesc('created_at')
                ->get();
                
            return response()->json($workouts);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch workouts',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $validated = $request->validate([
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

            // Ajouter automatiquement l'user_id de l'utilisateur connecté
            $validated['user_id'] = $user->id;

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

            return response()->json($workout->load([
                'user', 
                'exercises' => function($query) {
                    $query->with(['movement', 'machine', 'charge']);
                }
            ]), 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create workout',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Workout $workout)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Vérifier que le workout appartient à l'utilisateur connecté
            if ($workout->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return response()->json($workout->load([
                'user', 
                'exercises' => function($query) {
                    $query->with(['movement', 'machine', 'charge']);
                }
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch workout',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Workout $workout)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Vérifier que le workout appartient à l'utilisateur connecté
            if ($workout->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'water_consumption' => 'nullable|numeric|min:0',
                'duration' => 'nullable|integer|min:0',
                'is_rest_day' => 'boolean',
            ]);

            $workout->update($validated);
            return response()->json($workout->load([
                'user', 
                'exercises' => function($query) {
                    $query->with(['movement', 'machine', 'charge']);
                }
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update workout',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Workout $workout)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Vérifier que le workout appartient à l'utilisateur connecté
            if ($workout->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $workout->delete();
            return response()->json(['message' => 'Workout deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete workout',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function updateExerciseProgress(Request $request, Workout $workout, Exercise $exercise)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Vérifier que le workout appartient à l'utilisateur connecté
            if ($workout->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'achievement' => 'nullable|numeric|min:0|max:100',
                'is_done' => 'boolean',
            ]);

            $workout->exercises()->updateExistingPivot($exercise->id, $validated);
            return response()->json(['message' => 'Exercise progress updated successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update exercise progress',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getExercises(Workout $workout)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Vérifier que le workout appartient à l'utilisateur connecté
            if ($workout->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $exercises = $workout->exercises()->with(['movement', 'machine', 'charge'])->get();
            return response()->json($exercises);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch exercises',
                'details' => $e->getMessage()
            ], 500);
        }
    }
public function addExercise(Request $request, $workout)
{
    $workout = Workout::findOrFail($workout); // 👈 on charge manuellement

    $validated = $request->validate([
        'exercise_id' => 'required|exists:exercises,id',
        'achievement' => 'nullable|numeric|min:0|max:100',
        'is_done' => 'boolean',
        'order' => 'nullable|integer|min:0',
    ]);

    if ($workout->exercises()->where('exercise_id', $validated['exercise_id'])->exists()) {
        return response()->json(['error' => 'Exercise already linked to this workout'], 400);
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
    $workout->exercises()->detach($exercise->id);
    return response()->json(['message' => 'Exercise removed from workout successfully']);
}


public function updateExercisePivot(Request $request, $workoutId, $exerciseId)
{
    $workout = Workout::findOrFail($workoutId);

    $validated = $request->validate([
        'is_done' => 'sometimes|boolean',
        'achievement' => 'sometimes|numeric|min:0|max:100',
        'order' => 'sometimes|integer|min:0',
    ]);

    $workout->exercises()->updateExistingPivot($exerciseId, $validated);

    $updatedPivot = $workout->exercises()->where('exercise_id', $exerciseId)->first()->pivot;

    return response()->json([
        'message' => 'Pivot updated',
        'pivot' => $updatedPivot,
    ]);
}


}