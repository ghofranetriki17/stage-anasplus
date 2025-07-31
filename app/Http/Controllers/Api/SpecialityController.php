<?php

// Controller: SpecialityController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Speciality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SpecialityController extends Controller
{
    public function index()
    {
        $specialities = Speciality::with('coaches')->get();
        
        return response()->json([
            'success' => true,
            'data' => $specialities
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:specialities,name',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $speciality = Speciality::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Speciality created successfully',
            'data' => $speciality
        ], 201);
    }

    public function show($id)
    {
        $speciality = Speciality::with('coaches')->find($id);

        if (!$speciality) {
            return response()->json([
                'success' => false,
                'message' => 'Speciality not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $speciality
        ]);
    }

    public function update(Request $request, $id)
    {
        $speciality = Speciality::find($id);

        if (!$speciality) {
            return response()->json([
                'success' => false,
                'message' => 'Speciality not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:specialities,name,' . $id,
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $speciality->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Speciality updated successfully',
            'data' => $speciality
        ]);
    }

    public function destroy($id)
    {
        $speciality = Speciality::find($id);

        if (!$speciality) {
            return response()->json([
                'success' => false,
                'message' => 'Speciality not found'
            ], 404);
        }

        $speciality->delete();

        return response()->json([
            'success' => true,
            'message' => 'Speciality deleted successfully'
        ]);
    }
}
