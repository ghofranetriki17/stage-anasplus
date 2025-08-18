<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MachineController extends Controller
{
    public function index()
    {
        $machines = Machine::with(['branch', 'charges', 'categories'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $machines
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|string|max:255',
            'video_url' => 'nullable|string|max:255',
            'charge_ids' => 'nullable|array',
            'charge_ids.*' => 'exists:charges,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $machine = Machine::create($request->only([
            'branch_id', 'name', 'type', 'description', 'image_url', 'video_url'
        ]));

        // Attach charges if provided
        if ($request->has('charge_ids')) {
            $machine->charges()->attach($request->charge_ids);
        }

        // Attach categories if provided
        if ($request->has('category_ids')) {
            $machine->categories()->attach($request->category_ids);
        }

        $machine->load(['branch', 'charges', 'categories']);

        return response()->json([
            'success' => true,
            'message' => 'Machine created successfully',
            'data' => $machine
        ], 201);
    }

    public function show($id)
    {
        $machine = Machine::with(['branch', 'charges', 'categories'])->find($id);

        if (!$machine) {
            return response()->json([
                'success' => false,
                'message' => 'Machine not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $machine
        ]);
    }

    public function update(Request $request, $id)
    {
        $machine = Machine::find($id);

        if (!$machine) {
            return response()->json([
                'success' => false,
                'message' => 'Machine not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => 'sometimes|required|exists:branches,id',
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|string|max:255',
            'video_url' => 'nullable|string|max:255',
            'charge_ids' => 'nullable|array',
            'charge_ids.*' => 'exists:charges,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $machine->update($request->only([
            'branch_id', 'name', 'type', 'description', 'image_url', 'video_url'
        ]));

        // Update charges if provided
        if ($request->has('charge_ids')) {
            $machine->charges()->sync($request->charge_ids);
        }

        // Update categories if provided
        if ($request->has('category_ids')) {
            $machine->categories()->sync($request->category_ids);
        }

        $machine->load(['branch', 'charges', 'categories']);

        return response()->json([
            'success' => true,
            'message' => 'Machine updated successfully',
            'data' => $machine
        ]);
    }

    public function destroy($id)
    {
        $machine = Machine::find($id);

        if (!$machine) {
            return response()->json([
                'success' => false,
                'message' => 'Machine not found'
            ], 404);
        }

        $machine->delete();

        return response()->json([
            'success' => true,
            'message' => 'Machine deleted successfully'
        ]);
    }
public function syncCharges(Request $request, Machine $machine)
{
    $validated = $request->validate([
        'charge_ids' => 'required|array',
        'charge_ids.*' => 'exists:charges,id',
    ]);

    $machine->charges()->sync($validated['charge_ids']);

    return response()->json([
        'message' => 'Charges synchronisées avec succès',
        'machine' => $machine->load('charges')
    ]);
}


    public function getByBranch($branchId)
    {
        $machines = Machine::with(['charges', 'categories'])
            ->where('branch_id', $branchId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $machines
        ]);
    }
}