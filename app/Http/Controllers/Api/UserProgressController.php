<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserProgressController extends Controller
{
public function index()
{
    $user = Auth::user();

    $progresses = UserProgress::where('user_id', $user->id)
                              ->orderByDesc('recorded_at')
                              ->get();

    return response()->json($progresses);
}
     /* public function index(Request $request)
{
    try {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'User not authenticated'
            ], 401);
        }
        
        $progresses = UserProgress::where('user_id', $user->id)
            ->orderBy('recorded_at', 'desc')
            ->get();
            
        return response()->json($progresses);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to fetch progress data',
            'details' => $e->getMessage()
        ], 500);
    }
}*/


  public function store(Request $request)
{
    $user = $request->user(); // ou Auth::user();

    $validator = Validator::make($request->all(), [
        'weight' => 'required|numeric|min:30|max:300',
        'height' => 'required|numeric|min:100|max:250',
        'body_fat' => 'nullable|numeric|min:0|max:100',
        'muscle_mass' => 'nullable|numeric|min:0|max:100',
        'recorded_at' => 'required|date',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $data = $request->only(['weight', 'height', 'body_fat', 'muscle_mass', 'recorded_at']);
        $data['user_id'] = $user->id;
        $data['imc'] = $data['weight'] / (($data['height'] / 100) ** 2);

        $progress = UserProgress::create($data);

        return response()->json($progress, 201);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to create progress record',
            'details' => $e->getMessage()
        ], 500);
    }
}

   /* public function store(Request $request)
{
    $user = $request->user();

    $validator = Validator::make($request->all(), [
        'weight' => 'required|numeric|min:30|max:300',
        'height' => 'required|numeric|min:100|max:250',
        'body_fat' => 'nullable|numeric|min:0|max:100',
        'muscle_mass' => 'nullable|numeric|min:0|max:100',
        'recorded_at' => 'required|date',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $data = $request->only(['weight', 'height', 'body_fat', 'muscle_mass', 'recorded_at']);
        $data['user_id'] = $user->id;
        $data['imc'] = $data['weight'] / (($data['height'] / 100) ** 2);

        $progress = UserProgress::create($data);

        return response()->json($progress, 201);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to create progress record',
            'details' => $e->getMessage()
        ], 500);
    }
}*/
public function destroy($id)
{
    // Chercher la progression par ID
    $progress = UserProgress::find($id);

    if (!$progress) {
        return response()->json([
            'success' => false,
            'message' => 'Progress entry not found'
        ], 404);
    }

    try {
        $progress->delete();

        return response()->json([
            'success' => true,
            'message' => 'Progress entry deleted successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete progress',
            'error' => $e->getMessage()
        ], 500);
    }
}

}