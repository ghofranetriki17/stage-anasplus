<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserProgressController extends Controller
{
    public function index()
    {
        try {
            return UserProgress::all();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch progress data',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'weight' => 'required|numeric|min:30|max:300',
            'height' => 'required|numeric|min:100|max:250',
            'recorded_at' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['imc'] = $data['weight'] / (($data['height']/100) ** 2);
            
            $progress = UserProgress::create($data);
            
            return response()->json($progress, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create progress record',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}