<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserApprovalController extends Controller
{
    /**
     * Display a listing of pending user registrations awaiting approval.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');
        $unitFilter = $request->query('unit', '');

        $query = User::with('unit')
            ->where('is_active', false)
            ->latest();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if (!empty($unitFilter) && $unitFilter !== 'ALL') {
            $query->where('unit_id', $unitFilter);
        }

        $pendingUsers = $query->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username ?? '-',
                'nip' => $u->nip ?? '-',
                'email' => $u->email,
                'phone_number' => $u->phone_number ?? '-',
                'profile_photo_path' => $u->profile_photo_path,
                'role' => $u->role ?? 'STAFF',
                'unit_id' => $u->unit_id,
                'unit_name' => $u->unit ? $u->unit->name : 'Belum Ditentukan',
                'is_active' => (bool) $u->is_active,
                'created_at' => $u->created_at ? $u->created_at->format('d M Y, H:i') : '-',
                'created_at_human' => $u->created_at ? $u->created_at->diffForHumans() : '-',
            ];
        });

        $units = Unit::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        $stats = [
            'pending' => User::where('is_active', false)->count(),
            'approved_today' => User::where('is_active', true)->whereDate('updated_at', today())->count(),
            'total_active' => User::where('is_active', true)->count(),
        ];

        return Inertia::render('UserManagement/Approval/Index', [
            'users' => $pendingUsers,
            'units' => $units,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'unit' => $unitFilter,
            ],
        ]);
    }

    /**
     * Show detail of a user registration for approval.
     */
    public function show(User $user): Response
    {
        $user->load('unit');
        $units = Unit::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        return Inertia::render('UserManagement/Approval/Show', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username ?? '-',
                'nip' => $user->nip ?? '-',
                'email' => $user->email,
                'phone_number' => $user->phone_number ?? '-',
                'profile_photo_path' => $user->profile_photo_path,
                'role' => $user->role ?? 'STAFF',
                'unit_id' => $user->unit_id,
                'unit_name' => $user->unit ? $user->unit->name : 'Belum Ditentukan',
                'is_active' => (bool) $user->is_active,
                'created_at' => $user->created_at ? $user->created_at->format('d M Y, H:i') : '-',
                'created_at_human' => $user->created_at ? $user->created_at->diffForHumans() : '-',
            ],
            'units' => $units,
        ]);
    }

    /**
     * Approve user registration.
     */
    public function approve(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|string|in:ADMINISTRATOR,SUPERADMIN,KABID,KASI,STAFF',
            'unit_id' => 'nullable|exists:units,id',
        ]);

        $user->update([
            'role' => $validated['role'],
            'unit_id' => $validated['unit_id'] ?: null,
            'is_active' => true,
        ]);

        return redirect()->route('users.approvals')->with('success', "Akun {$user->name} berhasil disetujui dan diaktifkan.");
    }

    /**
     * Reject and delete user registration.
     */
    public function reject(User $user)
    {
        $userName = $user->name;
        $user->forceDelete();

        return redirect()->route('users.approvals')->with('success', "Pendaftaran akun {$userName} telah ditolak dan dihapus.");
    }
}
