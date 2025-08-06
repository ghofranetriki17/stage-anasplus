<?php
// Controller: CoachController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coach;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CoachController extends Controller
{
    public function index()
    {
        $coaches = Coach::with(['branch', 'specialities', 'availabilities'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $coaches
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:coaches,email|max:255',
            'phone' => 'required|string|max:20',
            'hourly_rate_online' => 'required|numeric|min:0',
            'hourly_rate_presential' => 'required|numeric|min:0',
            'bio' => 'nullable|string',
            'certifications' => 'nullable|string',
            'branch_id' => 'required|exists:branches,id',
            'specialities' => 'array',
            'specialities.*' => 'exists:specialities,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $coach = Coach::create($request->except('specialities'));
        
        if ($request->has('specialities')) {
            $coach->specialities()->attach($request->specialities);
        }

        $coach->load(['branch', 'specialities']);

        return response()->json([
            'success' => true,
            'message' => 'Coach created successfully',
            'data' => $coach
        ], 201);
    }

    public function show($id)
    {
$coach = Coach::with(['branch', 'specialities', 'availabilities', 'groupTrainingSessions', 'videos'])->find($id);

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Coach not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $coach
        ]);
    }

    public function update(Request $request, $id)
    {
        $coach = Coach::find($id);

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Coach not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:coaches,email,' . $id . '|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'hourly_rate_online' => 'sometimes|required|numeric|min:0',
            'hourly_rate_presential' => 'sometimes|required|numeric|min:0',
            'bio' => 'nullable|string',
            'certifications' => 'nullable|string',
            'is_available' => 'sometimes|boolean',
            'branch_id' => 'sometimes|required|exists:branches,id',
            'specialities' => 'array',
            'specialities.*' => 'exists:specialities,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $coach->update($request->except('specialities'));
        
        if ($request->has('specialities')) {
            $coach->specialities()->sync($request->specialities);
        }

        $coach->load(['branch', 'specialities']);

        return response()->json([
            'success' => true,
            'message' => 'Coach updated successfully',
            'data' => $coach
        ]);
    }

    public function destroy($id)
    {
        $coach = Coach::find($id);

        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Coach not found'
            ], 404);
        }

        $coach->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coach deleted successfully'
        ]);
    }
}
