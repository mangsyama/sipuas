<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    /**
     * Display a listing of all system users.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');
        $roleFilter = $request->query('role', '');
        $unitFilter = $request->query('unit', '');
        $statusFilter = $request->query('status', '');

        $query = User::with('unit')->latest();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if (!empty($roleFilter) && $roleFilter !== 'ALL') {
            $query->where('role', $roleFilter);
        }

        if (!empty($unitFilter) && $unitFilter !== 'ALL') {
            $query->where('unit_id', $unitFilter);
        }

        if ($statusFilter !== '' && $statusFilter !== 'ALL') {
            $isActive = $statusFilter === 'ACTIVE' || $statusFilter === '1';
            $query->where('is_active', $isActive);
        }

        $users = $query->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username ?? '-',
                'nip' => $u->nip ?? '-',
                'email' => $u->email,
                'phone_number' => $u->phone_number ?? '-',
                'role' => $u->role,
                'unit_id' => $u->unit_id,
                'unit_name' => $u->unit ? $u->unit->name : 'Semua Unit (Global)',
                'is_active' => (bool) $u->is_active,
                'created_at' => $u->created_at ? $u->created_at->format('Y-m-d H:i') : '-',
            ];
        });

        $units = Unit::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        $stats = [
            'total' => User::count(),
            'superadmin' => User::where('role', 'SUPERADMIN')->count(),
            'kabid' => User::where('role', 'KABID')->count(),
            'kasi' => User::where('role', 'KASI')->count(),
            'staff' => User::where('role', 'STAFF')->count(),
            'active' => User::where('is_active', true)->count(),
        ];

        return Inertia::render('UserManagement/Index', [
            'users' => $users,
            'units' => $units,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'unit' => $unitFilter,
                'status' => $statusFilter,
            ],
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users')->whereNull('deleted_at')],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('users')->whereNull('deleted_at')],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users')->whereNull('deleted_at')],
            'phone_number' => 'nullable|string|max:30',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:SUPERADMIN,KABID,KASI,STAFF',
            'unit_id' => 'nullable|exists:units,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = true;

        User::create($validated);

        return redirect()->back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Display the specified user detail.
     */
    public function show(User $user): Response
    {
        $user->load(['unit', 'verifiedReports']);

        return Inertia::render('UserManagement/Show', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username ?? '-',
                'nip' => $user->nip ?? '-',
                'email' => $user->email,
                'phone_number' => $user->phone_number ?? '-',
                'role' => $user->role,
                'unit_id' => $user->unit_id,
                'unit_name' => $user->unit ? $user->unit->name : 'Semua Unit (Global)',
                'is_active' => (bool) $user->is_active,
                'created_at' => $user->created_at ? $user->created_at->format('d M Y, H:i') : '-',
                'verified_reports_count' => $user->verifiedReports->count(),
            ],
        ]);
    }

    /**
     * Show form for editing user.
     */
    public function edit(User $user): Response
    {
        $units = Unit::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        return Inertia::render('UserManagement/Edit', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username ?? '',
                'nip' => $user->nip ?? '',
                'email' => $user->email,
                'phone_number' => $user->phone_number ?? '',
                'role' => $user->role,
                'unit_id' => $user->unit_id,
                'is_active' => (bool) $user->is_active,
            ],
            'units' => $units,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'phone_number' => 'nullable|string|max:30',
            'role' => 'required|string|in:SUPERADMIN,KABID,KASI,STAFF',
            'unit_id' => 'nullable|exists:units,id',
            'password' => 'nullable|string|min:6',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Toggle active status of user.
     */
    public function toggleStatus(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Akun pengguna berhasil {$statusText}.");
    }

    /**
     * Reset password of user.
     */
    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return redirect()->back()->with('success', 'Kata sandi pengguna berhasil direset.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
