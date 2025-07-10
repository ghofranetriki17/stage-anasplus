<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChargeController extends Controller
{
    public function index()
    {
        $charges = Charge::with('machines')->get();
        
        return response()->json([
            'success' => true,
            'data' => $charges
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'weight' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $charge = Charge::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Charge created successfully',
            'data' => $charge
        ], 201);
    }

    public function show($id)
    {
        $charge = Charge::with('machines')->find($id);

        if (!$charge) {
            return response()->json([
                'success' => false,
                'message' => 'Charge not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $charge
        ]);
    }

    public function update(Request $request, $id)
    {
        $charge = Charge::find($id);

        if (!$charge) {
            return response()->json([
                'success' => false,
                'message' => 'Charge not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'weight' => 'sometimes|required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $charge->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Charge updated successfully',
            'data' => $charge
        ]);
    }

    public function destroy($id)
    {
        $charge = Charge::find($id);

        if (!$charge) {
            return response()->json([
                'success' => false,
                'message' => 'Charge not found'
            ], 404);
        }

        $charge->delete();

        return response()->json([
            'success' => true,
            'message' => 'Charge deleted successfully'
        ]);
    }
}