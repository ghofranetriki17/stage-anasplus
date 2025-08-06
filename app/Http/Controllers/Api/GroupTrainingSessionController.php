<?php
// Controller: GroupTrainingSessionController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupTrainingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GroupTrainingSessionController extends Controller
{
    public function index()
    {
        $sessions = GroupTrainingSession::with(['branch', 'coach', 'course'])
            ->withCount('users')
            ->orderBy('session_date')
            ->get();
        
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
            'duration' => 'required|integer|min:15|max:480',
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
        $session = GroupTrainingSession::with(['branch', 'coach', 'course'])
            ->withCount('users')
            ->find($id);

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

    public function getByBranch($branchId)
    {
        $sessions = GroupTrainingSession::with(['coach', 'course'])
            ->withCount('users')
            ->where('branch_id', $branchId)
            ->orderBy('session_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    public function bookSession(Request $request, $sessionId)
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $session = GroupTrainingSession::findOrFail($sessionId);

            if ($session->session_date < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot book a session that has already started or ended'
                ], 400);
            }

            if ($session->isUserBooked($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already booked this session'
                ], 400);
            }

            if ($session->isFullyBooked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This session is fully booked'
                ], 409);
            }

            $user->groupSessions()->attach($sessionId, ['booked_at' => now()]);

            // ✅ Mise à jour du champ current_participants
            $session->current_participants = $session->getCurrentParticipantsCount();
            $session->save();

            DB::commit();

            $session->load(['branch', 'coach', 'course']);

            return response()->json([
                'success' => true,
                'message' => 'Session booked successfully',
                'data' => [
                    'session' => $session,
                    'current_participants' => $session->current_participants,
                    'available_spots' => $session->getAvailableSpots()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while booking the session'
            ], 500);
        }
    }

    public function cancelBooking(Request $request, $sessionId)
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $session = GroupTrainingSession::findOrFail($sessionId);

            if (!$session->isUserBooked($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have not booked this session'
                ], 400);
            }

            if ($session->session_date < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel a booking for a session that has already started or ended'
                ], 400);
            }

            $user->groupSessions()->detach($sessionId);

            // ✅ Mise à jour du champ current_participants après annulation
            $session->current_participants = $session->getCurrentParticipantsCount();
            $session->save();

            DB::commit();

            $session->load(['branch', 'coach', 'course']);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'data' => [
                    'session' => $session,
                    'current_participants' => $session->current_participants,
                    'available_spots' => $session->getAvailableSpots()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling the booking'
            ], 500);
        }
    }

    public function checkBookingStatus(Request $request, $sessionId)
    {
        $user = $request->user();
        $session = GroupTrainingSession::findOrFail($sessionId);

        return response()->json([
            'success' => true,
            'isBooked' => $session->isUserBooked($user->id),
            'currentParticipants' => $session->getCurrentParticipantsCount(),
            'maxParticipants' => $session->max_participants,
            'availableSpots' => $session->getAvailableSpots() ?? 999,
            'isFullyBooked' => $session->isFullyBooked()
        ]);
    }

    public function getUserBookings(Request $request)
    {
        $user = $request->user();
        
        $bookings = $user->groupSessions()
            ->with(['branch', 'coach', 'course'])
            ->withPivot('booked_at')
            ->orderBy('session_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    public function getSessionBookings(Request $request, $sessionId)
    {
        $session = GroupTrainingSession::with(['users' => function($query) {
            $query->withPivot('booked_at')->orderBy('pivot_booked_at');
        }])->findOrFail($sessionId);

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $session,
                'bookings' => $session->users,
                'total_bookings' => $session->users->count()
            ]
        ]);
    }

    public function getUpcoming(Request $request)
    {
        $sessions = GroupTrainingSession::with(['branch', 'coach', 'course'])
            ->withCount('users')
            ->upcoming()
            ->orderBy('session_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    public function getAvailable(Request $request)
    {
        $sessions = GroupTrainingSession::with(['branch', 'coach', 'course'])
            ->withCount('users')
            ->upcoming()
            ->available()
            ->orderBy('session_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }
}
