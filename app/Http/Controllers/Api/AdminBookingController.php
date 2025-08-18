<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminBookingController extends Controller
{
    private function ensureAdmin(Request $request)
    {
        $u = $request->user();
        if (!$u || ($u->role ?? 'user') !== 'admin') {
            abort(403, 'Forbidden');
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $v = Validator::make($request->all(), [
            'branch_id'   => 'sometimes|integer',
            'session_id'  => 'sometimes|integer',
            'coach_id'    => 'sometimes|integer',
            'course_id'   => 'sometimes|integer',
            'is_for_women'=> 'sometimes',
            'is_for_kids' => 'sometimes',
            'is_free'     => 'sometimes',
            'date_from'   => 'sometimes|date',
            'date_to'     => 'sometimes|date|after_or_equal:date_from',
            'q'           => 'sometimes|string|max:100',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $q = DB::table('group_session_bookings as b')
            ->join('group_training_sessions as s', 's.id', '=', 'b.group_training_session_id')
            ->join('branches as br', 'br.id', '=', 's.branch_id')
            ->leftJoin('coaches as c', 'c.id', '=', 's.coach_id')
            ->leftJoin('courses as co', 'co.id', '=', 's.course_id')
            ->join('users as u', 'u.id', '=', 'b.user_id')
            ->selectRaw("
                br.id as branch_id, br.name as branch_name,
                s.id as session_id, s.title as session_title, s.session_date, s.duration,
                s.is_for_women, s.is_for_kids, s.is_free, s.max_participants,
                c.id as coach_id, c.name as coach_name,
                co.id as course_id, co.name as course_name,
                b.id as booking_id, b.booked_at,
                u.id as user_id, u.name as user_name, u.email as user_email,
                '' as user_phone
            ");

        // --- Filtres ---
        if ($request->filled('branch_id'))   $q->where('s.branch_id', $request->branch_id);
        if ($request->filled('session_id'))  $q->where('s.id', $request->session_id);
        if ($request->filled('coach_id'))    $q->where('s.coach_id', $request->coach_id);
        if ($request->filled('course_id'))   $q->where('s.course_id', $request->course_id);

        foreach (['is_for_women','is_for_kids','is_free'] as $flag) {
            if ($request->has($flag)) {
                $val = filter_var($request->input($flag), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if (!is_null($val)) $q->where("s.$flag", $val);
            }
        }

        if ($request->filled('date_from'))   $q->whereDate('s.session_date', '>=', $request->date_from);
        if ($request->filled('date_to'))     $q->whereDate('s.session_date', '<=', $request->date_to);

        if ($request->filled('q')) {
            $term = '%'.trim($request->q).'%';
            $q->where(function($w) use ($term) {
                $w->where('u.name', 'like', $term)
                  ->orWhere('u.email', 'like', $term);
            });
        }

        $rows = $q->orderBy('br.name')
                  ->orderBy('s.session_date')
                  ->orderBy('b.booked_at')
                  ->get();

        // Grouping: Branch -> Sessions -> Bookings
        $byBranch = [];
        foreach ($rows as $r) {
            if (!isset($byBranch[$r->branch_id])) {
                $byBranch[$r->branch_id] = [
                    'branch' => ['id'=>$r->branch_id, 'name'=>$r->branch_name],
                    'sessions' => [],
                ];
            }
            if (!isset($byBranch[$r->branch_id]['sessions'][$r->session_id])) {
                $byBranch[$r->branch_id]['sessions'][$r->session_id] = [
                    'session' => [
                        'id' => $r->session_id,
                        'title' => $r->session_title,
                        'session_date' => $r->session_date,
                        'duration' => (int)$r->duration,
                        'is_for_women' => (bool)$r->is_for_women,
                        'is_for_kids'  => (bool)$r->is_for_kids,
                        'is_free'      => (bool)$r->is_free,
                        'max_participants' => $r->max_participants,
                        'coach'  => $r->coach_id ? ['id'=>$r->coach_id,'name'=>$r->coach_name] : null,
                        'course' => $r->course_id ? ['id'=>$r->course_id,'name'=>$r->course_name] : null,
                    ],
                    'bookings' => [],
                ];
            }
            $byBranch[$r->branch_id]['sessions'][$r->session_id]['bookings'][] = [
                'booking_id' => $r->booking_id,
                'booked_at'  => $r->booked_at,
                'user' => [
                    'id'    => $r->user_id,
                    'name'  => $r->user_name,
                    'email' => $r->user_email,
                    'phone' => $r->user_phone, // vide ici
                ],
            ];
        }

        // Normaliser en listes + totaux
        $branches = [];
        foreach ($byBranch as $branch) {
            $sessions = [];
            foreach ($branch['sessions'] as $s) {
                $count = count($s['bookings']);
                $max   = $s['session']['max_participants'];
                $sessions[] = [
                    'session'        => $s['session'],
                    'totals'         => [
                        'bookings' => $count,
                        'available_spots' => is_null($max) ? null : max(0, $max - $count),
                    ],
                    'bookings'       => $s['bookings'],
                ];
            }
            $branches[] = [
                'branch'   => $branch['branch'],
                'totals'   => [
                    'sessions' => count($sessions),
                    'bookings' => array_sum(array_map(fn($x)=>$x['totals']['bookings'], $sessions)),
                ],
                'sessions' => $sessions,
            ];
        }

        return response()->json([
            'success' => true,
            'filters' => ['applied' => $request->all()],
            'data' => $branches,
        ]);
    }
}
