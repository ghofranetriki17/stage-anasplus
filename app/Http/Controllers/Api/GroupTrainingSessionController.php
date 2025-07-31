<?php
// Controller: GroupTrainingSessionController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupTrainingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupTrainingSessionController extends Controller
{
    public function index()
    {
        $sessions = GroupTrainingSession::with(['branch', 'coach', 'course'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'coach_id' => 'required|exists:coaches,id',
            'course_id' => 'required|exists:courses,id',
            'session_date' => 'required|date|after:now',
            'duration' => 'required|integer|min:15|max:480', // 15 minutes to 8 hours
            'title' => 'required|string|max:255',
            'is_for_women' => 'boolean',
            'is_free' => 'boolean',
            'is_for_kids' => 'boolean',
            'max_participants' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $session = GroupTrainingSession::create($request->all());
        $session->load(['branch', 'coach', 'course']);

        return response()->json([
            'success' => true,
            'message' => 'Group training session created successfully',
            'data' => $session
        ], 201);
    }

    public function show($id)
    {
        $session = GroupTrainingSession::with(['branch', 'coach', 'course'])->find($id);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Group training session not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    }

    public function update(Request $request, $id)
    {
        $session = GroupTrainingSession::find($id);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Group training session not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => 'sometimes|required|exists:branches,id',
            'coach_id' => 'sometimes|required|exists:coaches,id',
            'course_id' => 'sometimes|required|exists:courses,id',
            'session_date' => 'sometimes|required|date',
            'duration' => 'sometimes|required|integer|min:15|max:480',
            'title' => 'sometimes|required|string|max:255',
            'is_for_women' => 'boolean',
            'is_free' => 'boolean',
            'is_for_kids' => 'boolean',
            'max_participants' => 'nullable|integer|min:1',
            'current_participants' => 'sometimes|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $session->update($request->all());
        $session->load(['branch', 'coach', 'course']);

        return response()->json([
            'success' => true,
            'message' => 'Group training session updated successfully',
            'data' => $session
        ]);
    }

    public function destroy($id)
    {
        $session = GroupTrainingSession::find($id);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Group training session not found'
            ], 404);
        }

        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group training session deleted successfully'
        ]);
    }

    public function getByBranch($branchId)
    {
        $sessions = GroupTrainingSession::with(['coach', 'course'])
            ->where('branch_id', $branchId)
            ->orderBy('session_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    public function getByCoach($coachId)
    {
        $sessions = GroupTrainingSession::with(['branch', 'course'])
            ->where('coach_id', $coachId)
            ->orderBy('session_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    public function getUpcoming()
    {
        $sessions = GroupTrainingSession::with(['branch', 'coach', 'course'])
            ->where('session_date', '>', now())
            ->orderBy('session_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    public function joinSession(Request $request, $id)
    {
        $session = GroupTrainingSession::find($id);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Group training session not found'
            ], 404);
        }

        if ($session->isFullyBooked()) {
            return response()->json([
                'success' => false,
                'message' => 'Session is fully booked'
            ], 400);
        }

        $session->increment('current_participants');

        return response()->json([
            'success' => true,
            'message' => 'Successfully joined the session',
            'data' => $session->fresh(['branch', 'coach', 'course'])
        ]);
    }

    public function leaveSession(Request $request, $id)
    {
        $session = GroupTrainingSession::find($id);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Group training session not found'
            ], 404);
        }

        if ($session->current_participants > 0) {
            $session->decrement('current_participants');
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully left the session',
            'data' => $session->fresh(['branch', 'coach', 'course'])
        ]);
    }

    public function getSessionsByFilters(Request $request)
    {
        $query = GroupTrainingSession::with(['branch', 'coach', 'course']);

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by coach
        if ($request->has('coach_id')) {
            $query->where('coach_id', $request->coach_id);
        }

        // Filter by course
        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('session_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('session_date', '<=', $request->end_date);
        }

        // Filter by session type
        if ($request->has('is_for_women')) {
            $query->where('is_for_women', $request->boolean('is_for_women'));
        }

        if ($request->has('is_for_kids')) {
            $query->where('is_for_kids', $request->boolean('is_for_kids'));
        }

        if ($request->has('is_free')) {
            $query->where('is_free', $request->boolean('is_free'));
        }

        // Filter by availability
        if ($request->has('available_only') && $request->boolean('available_only')) {
            $query->whereRaw('current_participants < max_participants OR max_participants IS NULL');
        }

        $sessions = $query->orderBy('session_date')->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }
    // GroupTrainingSessionController.php

public function bookSession(Request $request, $sessionId)
{
    $user = $request->user();
    $session = GroupTrainingSession::findOrFail($sessionId);

    // Check if already booked
    if ($user->groupSessions()->where('group_training_session_id', $sessionId)->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'You have already booked this session'
        ], 400);
    }

    // Check if session is full
    if ($session->isFullyBooked()) {
        return response()->json([
            'success' => false,
            'message' => 'This session is fully booked'
        ], 400);
    }

    // Book the session
    $user->groupSessions()->attach($sessionId);

    return response()->json([
        'success' => true,
        'message' => 'Session booked successfully',
        'data' => [
            'session' => $session->fresh(['users', 'branch', 'coach', 'course']),
            'remaining_spots' => $session->max_participants - $session->users()->count()
        ]
    ]);
}

public function cancelBooking(Request $request, $sessionId)
{
    $user = $request->user();
    $session = GroupTrainingSession::findOrFail($sessionId);

    if (!$user->groupSessions()->where('group_training_session_id', $sessionId)->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'You have not booked this session'
        ], 400);
    }

    $user->groupSessions()->detach($sessionId);

    return response()->json([
        'success' => true,
        'message' => 'Booking cancelled successfully',
        'data' => [
            'session' => $session->fresh(['users', 'branch', 'coach', 'course']),
            'remaining_spots' => $session->max_participants - $session->users()->count()
        ]
    ]);
}

public function getUserBookings(Request $request)
{
    $user = $request->user();
    
    $bookings = $user->groupSessions()
        ->with(['branch', 'coach', 'course'])
        ->orderBy('session_date')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $bookings
    ]);
}
}