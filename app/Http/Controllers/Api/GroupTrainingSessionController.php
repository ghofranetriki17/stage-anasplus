<?php
// app/Http/Controllers/Api/GroupTrainingSessionController.php

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
            'data' => $sessions,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id'        => 'required|exists:branches,id',
            'coach_id'         => 'required|exists:coaches,id',
            'course_id'        => 'required|exists:courses,id',
            'session_date'     => 'required|date|after:now',
            'duration'         => 'required|integer|min:15|max:480',
            'title'            => 'required|string|max:255',
            'is_for_women'     => 'boolean',
            'is_free'          => 'boolean',
            'is_for_kids'      => 'boolean',
            'max_participants' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Normaliser les booléens au cas où ils arrivent en string "true"/"false"/"1"/"0"
        $payload = $this->normalizeBooleanFlags($request->only([
            'branch_id', 'coach_id', 'course_id', 'session_date', 'duration', 'title',
            'is_for_women', 'is_free', 'is_for_kids', 'max_participants',
        ]));

        $session = GroupTrainingSession::create($payload);
        $session->load(['branch', 'coach', 'course'])->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Group training session created successfully',
            'data'    => $session,
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
                'message' => 'Group training session not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $session,
        ]);
    }

    public function update(Request $request, $id)
    {
        $session = GroupTrainingSession::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'branch_id'        => 'sometimes|exists:branches,id',
            'coach_id'         => 'sometimes|exists:coaches,id',
            'course_id'        => 'sometimes|exists:courses,id',
            'session_date'     => 'sometimes|date|after:now',
            'duration'         => 'sometimes|integer|min:15|max:480',
            'title'            => 'sometimes|string|max:255',
            'is_for_women'     => 'sometimes|boolean',
            'is_free'          => 'sometimes|boolean',
            'is_for_kids'      => 'sometimes|boolean',
            'max_participants' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $payload = $this->normalizeBooleanFlags($request->only([
            'branch_id', 'coach_id', 'course_id', 'session_date', 'duration', 'title',
            'is_for_women', 'is_free', 'is_for_kids', 'max_participants',
        ]));

        $session->fill($payload);
        $session->save();

        // Optionnel : mettre à jour le compteur courant si la capacité a changé (pas strictement nécessaire)
        $session->current_participants = $session->getCurrentParticipantsCount();
        $session->save();

        $session->load(['branch', 'coach', 'course'])->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Group training session updated successfully',
            'data'    => $session,
        ]);
    }

    public function destroy($id)
    {
        $session = GroupTrainingSession::findOrFail($id);

        // Détacher les réservations liées pour éviter des contraintes pivot
        $session->users()->detach();

        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group training session deleted successfully',
        ], 200);
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
            'data'    => $sessions,
        ]);
    }

    public function getByCoach($coachId)
    {
        $sessions = GroupTrainingSession::with(['branch', 'course'])
            ->withCount('users')
            ->where('coach_id', $coachId)
            ->orderBy('session_date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $sessions,
        ]);
    }

    public function getSessionsByFilters(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id'    => 'sometimes|exists:branches,id',
            'coach_id'     => 'sometimes|exists:coaches,id',
            'course_id'    => 'sometimes|exists:courses,id',
            'is_for_women' => 'sometimes|boolean',
            'is_for_kids'  => 'sometimes|boolean',
            'is_free'      => 'sometimes|boolean',
            'date_from'    => 'sometimes|date',
            'date_to'      => 'sometimes|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $q = GroupTrainingSession::with(['branch', 'coach', 'course'])->withCount('users');

        if ($request->filled('branch_id')) {
            $q->where('branch_id', $request->branch_id);
        }
        if ($request->filled('coach_id')) {
            $q->where('coach_id', $request->coach_id);
        }
        if ($request->filled('course_id')) {
            $q->where('course_id', $request->course_id);
        }

        // Normaliser bools si fournis
        foreach (['is_for_women', 'is_for_kids', 'is_free'] as $flag) {
            if ($request->has($flag)) {
                $val = filter_var($request->input($flag), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if (!is_null($val)) {
                    $q->where($flag, $val);
                }
            }
        }

        if ($request->filled('date_from')) {
            $q->where('session_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->where('session_date', '<=', $request->date_to);
        }

        $sessions = $q->orderBy('session_date')->get();

        return response()->json([
            'success' => true,
            'data'    => $sessions,
        ]);
    }

    public function bookSession(Request $request, $sessionId)
    {
        try {
            DB::beginTransaction();

            $user    = $request->user();
            $session = GroupTrainingSession::findOrFail($sessionId);

            if ($session->session_date < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot book a session that has already started or ended',
                ], 400);
            }

            if ($session->isUserBooked($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already booked this session',
                ], 400);
            }

            if ($session->isFullyBooked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This session is fully booked',
                ], 409);
            }

            $user->groupSessions()->attach($sessionId, ['booked_at' => now()]);

            $session->current_participants = $session->getCurrentParticipantsCount();
            $session->save();

            DB::commit();

            $session->load(['branch', 'coach', 'course']);

            return response()->json([
                'success' => true,
                'message' => 'Session booked successfully',
                'data'    => [
                    'session'               => $session,
                    'current_participants'  => $session->current_participants,
                    'available_spots'       => $session->getAvailableSpots(),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while booking the session',
            ], 500);
        }
    }

    public function cancelBooking(Request $request, $sessionId)
    {
        try {
            DB::beginTransaction();

            $user    = $request->user();
            $session = GroupTrainingSession::findOrFail($sessionId);

            if (!$session->isUserBooked($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have not booked this session',
                ], 400);
            }

            if ($session->session_date < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel a booking for a session that has already started or ended',
                ], 400);
            }

            $user->groupSessions()->detach($sessionId);

            $session->current_participants = $session->getCurrentParticipantsCount();
            $session->save();

            DB::commit();

            $session->load(['branch', 'coach', 'course']);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'data'    => [
                    'session'               => $session,
                    'current_participants'  => $session->current_participants,
                    'available_spots'       => $session->getAvailableSpots(),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling the booking',
            ], 500);
        }
    }

    public function checkBookingStatus(Request $request, $sessionId)
    {
        $user    = $request->user();
        $session = GroupTrainingSession::findOrFail($sessionId);

        return response()->json([
            'success'             => true,
            'isBooked'            => $session->isUserBooked($user->id),
            'currentParticipants' => $session->getCurrentParticipantsCount(),
            'maxParticipants'     => $session->max_participants,
            'availableSpots'      => $session->getAvailableSpots() ?? 999,
            'isFullyBooked'       => $session->isFullyBooked(),
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
            'data'    => $bookings,
        ]);
    }

    public function getSessionBookings(Request $request, $sessionId)
    {
        $session = GroupTrainingSession::with(['users' => function ($query) {
            $query->withPivot('booked_at')->orderBy('pivot_booked_at');
        }])->findOrFail($sessionId);

        return response()->json([
            'success' => true,
            'data'    => [
                'session'         => $session,
                'bookings'        => $session->users,
                'total_bookings'  => $session->users->count(),
            ],
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
            'data'    => $sessions,
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
            'data'    => $sessions,
        ]);
    }

    // ---------- Helpers ----------
    private function normalizeBooleanFlags(array $payload): array
    {
        foreach (['is_for_women', 'is_free', 'is_for_kids'] as $key) {
            if (array_key_exists($key, $payload)) {
                // accepte true/false/"true"/"false"/1/0
                $val = filter_var($payload[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if (!is_null($val)) {
                    $payload[$key] = $val;
                }
            }
        }
        return $payload;
    }
}
