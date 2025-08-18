<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Only admins can manage users — simple gate
    private function ensureAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || ($user->role ?? 'user') !== 'admin') {
            abort(403, 'Forbidden');
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $q    = $request->query('q');
        $role = $request->query('role');
        $per  = (int)($request->query('per_page', 0)); // if > 0 => paginate

        $query = User::query();

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%");
            });
        }
        if ($role) {
            $query->where('role', $role);
        }

        $query->orderByDesc('created_at');

        if ($per > 0) {
            // paginator shape: { data: [...], links, meta }
            return response()->json($query->paginate($per));
        }

        // plain array shape: [...]
        return response()->json($query->get());
    }

    public function show(Request $request, User $user)
    {
        $this->ensureAdmin($request);
        return response()->json($user);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'role'     => ['required', Rule::in(['admin','user'])], // adapte si tu as d’autres rôles
            'password' => ['required','string','min:6','confirmed'], // needs password_confirmation
        ]);

        // User model has 'password' => 'hashed' cast, so this will be hashed automatically
        $user = User::create($data);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name'     => ['sometimes','string','max:255'],
            'email'    => ['sometimes','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'role'     => ['sometimes', Rule::in(['admin','user'])],
            'password' => ['nullable','string','min:6','confirmed'], // send password_confirmation when present
        ]);

        // if password not sent or empty, don’t overwrite
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $user->delete();
        return response()->json(['deleted' => true]);
    }
}
