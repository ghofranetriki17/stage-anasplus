<?php
// Controller: CoachAvailabilityController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoachAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CoachAvailabilityController extends Controller
{
    public function index()
    {
        $availabilities = CoachAvailability::with('coach')->get();
        
        return response()->json([
            'success' => true,
            'data' => $availabilities
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coach_id' => 'required|exists:coaches,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_available' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $availability = CoachAvailability::create($request->all());
        $availability->load('coach');

        return response()->json([
            'success' => true,
            'message' => 'Coach availability created successfully',
            'data' => $availability
        ], 201);
    }

    public function show($id)
    {
        $availability = CoachAvailability::with('coach')->find($id);

        if (!$availability) {
            return response()->json([
                'success' => false,
                'message' => 'Coach availability not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $availability
        ]);
    }

    public function update(Request $request, $id)
    {
        $availability = CoachAvailability::find($id);

        if (!$availability) {
            return response()->json([
                'success' => false,
                'message' => 'Coach availability not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'coach_id' => 'sometimes|required|exists:coaches,id',
            'day_of_week' => 'sometimes|required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'is_available' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $availability->update($request->all());
        $availability->load('coach');

        return response()->json([
            'success' => true,
            'message' => 'Coach availability updated successfully',
            'data' => $availability
        ]);
    }

    public function destroy($id)
    {
        $availability = CoachAvailability::find($id);

        if (!$availability) {
            return response()->json([
                'success' => false,
                'message' => 'Coach availability not found'
            ], 404);
        }

        $availability->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coach availability deleted successfully'
        ]);
    }

    public function getByCoach($coachId)
    {
        $availabilities = CoachAvailability::where('coach_id', $coachId)
            ->where('is_available', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $availabilities
        ]);
    }
}