<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchAvailabilityController extends Controller
{
    public function index($branchId)
    {
        $branch = Branch::find($branchId);
        
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found'
            ], 404);
        }

        $availabilities = $branch->availabilities()->get();

        return response()->json([
            'success' => true,
            'data' => $availabilities
        ]);
    }

    public function store(Request $request, $branchId)
    {
        $branch = Branch::find($branchId);
        
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'opening_hour' => 'required_if:is_closed,false|date_format:H:i',
            'closing_hour' => 'required_if:is_closed,false|date_format:H:i|after:opening_hour',
            'is_closed' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['branch_id'] = $branchId;

        // If closed, set opening and closing hours to null
        if ($data['is_closed']) {
            $data['opening_hour'] = null;
            $data['closing_hour'] = null;
        }

        $availability = BranchAvailability::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Availability created successfully',
            'data' => $availability
        ], 201);
    }

    public function show($branchId, $id)
    {
        $availability = BranchAvailability::where('branch_id', $branchId)
            ->where('id', $id)
            ->first();

        if (!$availability) {
            return response()->json([
                'success' => false,
                'message' => 'Availability not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $availability
        ]);
    }

    public function update(Request $request, $branchId, $id)
    {
        $availability = BranchAvailability::where('branch_id', $branchId)
            ->where('id', $id)
            ->first();

        if (!$availability) {
            return response()->json([
                'success' => false,
                'message' => 'Availability not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'day_of_week' => 'sometimes|required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'opening_hour' => 'required_if:is_closed,false|date_format:H:i',
            'closing_hour' => 'required_if:is_closed,false|date_format:H:i|after:opening_hour',
            'is_closed' => 'sometimes|required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        // If closed, set opening and closing hours to null
        if (isset($data['is_closed']) && $data['is_closed']) {
            $data['opening_hour'] = null;
            $data['closing_hour'] = null;
        }

        $availability->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Availability updated successfully',
            'data' => $availability
        ]);
    }

    public function destroy($branchId, $id)
    {
        $availability = BranchAvailability::where('branch_id', $branchId)
            ->where('id', $id)
            ->first();

        if (!$availability) {
            return response()->json([
                'success' => false,
                'message' => 'Availability not found'
            ], 404);
        }

        $availability->delete();

        return response()->json([
            'success' => true,
            'message' => 'Availability deleted successfully'
        ]);
    }

    // Method to get availability for a specific day
    public function getAvailabilityForDay($branchId, $dayOfWeek)
    {
        $branch = Branch::find($branchId);
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found'
            ], 404);
        }

        $availability = BranchAvailability::where('branch_id', $branchId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$availability) {
            return response()->json([
                'success' => false,
                'message' => 'No availability found for this day'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $availability
        ]);
    }
}