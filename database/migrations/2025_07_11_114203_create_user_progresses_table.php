<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserProgressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $progresses = UserProgress::where('user_id', $user->id)
                ->orderBy('recorded_at', 'desc')
                ->get();

            return response()->json($progresses);
        } catch (\Exception $e) {
            Log::error('UserProgress index error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch progress', 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $validated = $request->validate([
                'weight' => 'nullable|numeric|min:0|max:1000',
                'height' => 'nullable|numeric|min:0|max:300',
                'body_fat' => 'nullable|numeric|min:0|max:100',
                'muscle_mass' => 'nullable|numeric|min:0|max:100',
                'recorded_at' => 'required|date',
            ]);

            // Automatically set the user_id to the authenticated user
            $validated['user_id'] = $user->id;

            // Calculate BMI (IMC) only if both weight and height are provided
            if (isset($validated['weight']) && isset($validated['height']) && $validated['weight'] > 0 && $validated['height'] > 0) {
                $heightInMeters = $validated['height'] / 100;
                $bmi = $validated['weight'] / ($heightInMeters * $heightInMeters);
                $validated['imc'] = round($bmi, 2);
            }

            $progress = UserProgress::create($validated);

            return response()->json($progress, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('UserProgress store error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create progress', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $progress = UserProgress::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$progress) {
                return response()->json(['error' => 'Progress not found'], 404);
            }

            return response()->json($progress);
        } catch (\Exception $e) {
            Log::error('UserProgress show error: ' . $e->getMessage());
            return response()->json(['error' => 'Progress not found', 'message' => $e->getMessage()], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $progress = UserProgress::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$progress) {
                return response()->json(['error' => 'Progress not found'], 404);
            }

            $validated = $request->validate([
                'weight' => 'sometimes|nullable|numeric|min:0|max:1000',
                'height' => 'sometimes|nullable|numeric|min:0|max:300',
                'body_fat' => 'nullable|numeric|min:0|max:100',
                'muscle_mass' => 'nullable|numeric|min:0|max:100',
                'recorded_at' => 'sometimes|required|date',
            ]);

            // Recalculate BMI if weight or height changed
            if ((isset($validated['weight']) && $validated['weight'] > 0) || (isset($validated['height']) && $validated['height'] > 0)) {
                $weight = $validated['weight'] ?? $progress->weight;
                $height = $validated['height'] ?? $progress->height;
                
                if ($weight > 0 && $height > 0) {
                    $heightInMeters = $height / 100;
                    $bmi = $weight / ($heightInMeters * $heightInMeters);
                    $validated['imc'] = round($bmi, 2);
                }
            }

            $progress->update($validated);

            return response()->json($progress);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('UserProgress update error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update progress', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $progress = UserProgress::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$progress) {
                return response()->json(['error' => 'Progress not found'], 404);
            }

            $progress->delete();

            return response()->json(['message' => 'Progress deleted successfully']);
        } catch (\Exception $e) {
            Log::error('UserProgress destroy error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete progress', 'message' => $e->getMessage()], 500);
        }
    }

    public function history()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $progresses = UserProgress::where('user_id', $user->id)
                ->orderBy('recorded_at', 'asc')
                ->get();

            return response()->json($progresses);
        } catch (\Exception $e) {
            Log::error('UserProgress history error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch progress history', 'message' => $e->getMessage()], 500);
        }
    }
}