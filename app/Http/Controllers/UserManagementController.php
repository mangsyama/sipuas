<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Channels\WaGatewayChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    /**
     * Get all permission keys structured by category groups.
     */
    public static function getAllPermissionKeys(): array
    {
        return [
            [
                'group' => 'Menu Utama',
                'permissions' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard Utama'],
                ],
            ],
            [
                'group' => 'Modul Staf Pelayanan',
                'permissions' => [
                    ['key' => 'staff.attendance', 'label' => 'Presensi Mandiri / Live Attendance'],
                    ['key' => 'staff.dashboard', 'label' => 'Dashboard Kinerja Staf & Presensi'],
                    ['key' => 'attendance.status', 'label' => 'Akses Status Presensi Mandiri'],
                ],
            ],
            [
                'group' => 'Modul Kepala Seksi (Kasi)',
                'permissions' => [
                    ['key' => 'kasi.dashboard', 'label' => 'Feed Aduan & Monitoring Shift'],
                    ['key' => 'kasi.verify', 'label' => 'Verifikasi Laporan & Evaluasi KPI'],
                    ['key' => 'kasi.logbook', 'label' => 'Digital Logbook Staf Unit'],
                ],
            ],
            [
                'group' => 'Modul Kepala Bidang (Kabid)',
                'permissions' => [
                    ['key' => 'executive.dashboard', 'label' => 'Executive Analytics & Responsiveness'],
                    ['key' => 'executive.kasi-responsiveness', 'label' => 'Tingkat Responsivitas Kasi'],
                    ['key' => 'executive.leaderboard', 'label' => 'Leaderboard Integritas Unit'],
                ],
            ],
            [
                'group' => 'Master Data & Pengaturan',
                'permissions' => [
                    ['key' => 'units.index', 'label' => 'Master Ruangan RS'],
                    ['key' => 'rooms.index', 'label' => 'Master Ruangan RS'],
                    ['key' => 'users.approvals', 'label' => 'Persetujuan Pendaftar Baru'],
                    ['key' => 'users.index', 'label' => 'Kelola Akun Sistem'],
                    ['key' => 'admin.ai-settings.index', 'label' => 'Integrasi AI (Gemini/Groq/OpenAI)'],
                    ['key' => 'admin.wa-gateway.index', 'label' => 'WhatsApp Gateway Management'],
                    ['key' => 'settings.index', 'label' => 'Preferensi Notifikasi Akun'],
                ],
            ],
        ];
    }

    /**
     * Display a listing of system users with comprehensive filters.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');
        $roleFilter = $request->query('role', '');
        $unitFilter = $request->query('unit', '');
        $statusFilter = $request->query('status', '');

        $query = User::with('room')->latest();

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
            $roleId = match ($roleFilter) {
                'ADMINISTRATOR', 'SUPERADMIN' => Role::ADMINISTRATOR,
                'DIREKTUR' => Role::DIREKTUR,
                'KABID' => Role::KEPALA_BIDANG,
                'KASI' => Role::KEPALA_SEKSI,
                default => Role::STAFF,
            };
            $query->where('role_id', $roleId);
        }

        if (!empty($unitFilter) && $unitFilter !== 'ALL') {
            $query->where(function ($q) use ($unitFilter) {
                $q->where('room_id', $unitFilter)->orWhere('unit_id', $unitFilter);
            });
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
                'profile_photo_path' => $u->profile_photo_path,
                'role' => $u->role,
                'role_id' => $u->role_id,
                'room_id' => $u->room_id,
                'unit_id' => $u->room_id,
                'unit_name' => $u->room ? $u->room->name : 'Semua Ruangan (Global)',
                'room_name' => $u->room ? $u->room->name : 'Semua Ruangan (Global)',
                'room_location' => $u->room ? $u->room->location_info : '-',
                'is_active' => (bool) $u->is_active,
                'created_at' => $u->created_at ? $u->created_at->format('Y-m-d H:i') : '-',
            ];
        });

        $units = Room::where('is_active', true)->orderBy('building_name')->orderBy('name')->get(['id', 'name', 'building_name', 'location_floor'])->map(function ($r) {
            return [
                'id' => $r->id,
                'code' => $r->location_info,
                'name' => $r->name,
                'building_name' => $r->building_name,
                'location_floor' => $r->location_floor,
            ];
        });
        $roles = Role::orderBy('id', 'asc')->get();

        $stats = [
            'total' => User::count(),
            'administrator' => User::where('role_id', Role::ADMINISTRATOR)->count(),
            'superadmin' => User::where('role_id', Role::ADMINISTRATOR)->count(),
            'direktur' => User::where('role_id', Role::DIREKTUR)->count(),
            'kabid' => User::where('role_id', Role::KEPALA_BIDANG)->count(),
            'kasi' => User::where('role_id', Role::KEPALA_SEKSI)->count(),
            'staff' => User::where('role_id', Role::STAFF)->count(),
            'active' => User::where('is_active', true)->count(),
        ];

        return Inertia::render('UserManagement/Index', [
            'users' => $users,
            'units' => $units,
            'rooms' => $units,
            'roles' => $roles,
            'stats' => $stats,
            'allPermissionKeys' => self::getAllPermissionKeys(),
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
            'role' => 'required|string|in:ADMINISTRATOR,SUPERADMIN,DIREKTUR,KABID,KASI,STAFF',
            'room_id' => 'nullable|exists:rooms,id',
            'unit_id' => 'nullable|exists:rooms,id',
        ]);

        if (!empty($validated['unit_id']) && empty($validated['room_id'])) {
            $validated['room_id'] = $validated['unit_id'];
        }

        $roleId = match ($validated['role']) {
            'ADMINISTRATOR', 'SUPERADMIN' => Role::ADMINISTRATOR,
            'DIREKTUR' => Role::DIREKTUR,
            'KABID' => Role::KEPALA_BIDANG,
            'KASI' => Role::KEPALA_SEKSI,
            default => Role::STAFF,
        };

        $validated['role_id'] = $roleId;
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
        $user->load(['room', 'verifiedReports']);

        return Inertia::render('UserManagement/Show', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username ?? '-',
                'nip' => $user->nip ?? '-',
                'email' => $user->email,
                'phone_number' => $user->phone_number ?? '-',
                'profile_photo_path' => $user->profile_photo_path,
                'role' => $user->role,
                'role_id' => $user->role_id,
                'room_id' => $user->room_id,
                'unit_id' => $user->room_id,
                'room_name' => $user->room ? $user->room->name : 'Semua Ruangan (Global)',
                'unit_name' => $user->room ? $user->room->name : 'Semua Ruangan (Global)',
                'room_location' => $user->room ? $user->room->location_info : '-',
                'is_active' => (bool) $user->is_active,
                'total_points' => (int) $user->total_points,
                'praise_count' => (int) $user->praise_count,
                'complaint_count' => (int) $user->complaint_count,
                'created_at' => $user->created_at ? $user->created_at->format('d M Y, H:i') : '-',
                'verified_reports_count' => $user->verifiedReports->count(),
                'effective_permissions' => $user->getEffectivePermissions(),
                'has_custom_permissions' => $user->page_permissions !== null,
            ],
            'allPermissionKeys' => self::getAllPermissionKeys(),
        ]);
    }

    /**
     * Show form for editing user.
     */
    public function edit(User $user): Response
    {
        $rooms = Room::where('is_active', true)->orderBy('building_name')->orderBy('name')->get();
        $units = $rooms->map(fn ($r) => [
            'id' => $r->id,
            'code' => $r->location_info,
            'name' => $r->name,
            'building_name' => $r->building_name,
            'location_floor' => $r->location_floor,
        ]);
        $roles = Role::orderBy('id', 'asc')->get();

        return Inertia::render('UserManagement/Edit', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username ?? '',
                'nip' => $user->nip ?? '',
                'email' => $user->email,
                'phone_number' => $user->phone_number ?? '',
                'profile_photo_path' => $user->profile_photo_path,
                'role' => $user->role,
                'role_id' => $user->role_id,
                'room_id' => $user->room_id,
                'unit_id' => $user->room_id,
                'is_active' => (bool) $user->is_active,
                'page_permissions' => $user->page_permissions,
                'effective_permissions' => $user->getEffectivePermissions(),
                'use_role_default' => $user->page_permissions === null,
            ],
            'units' => $units,
            'rooms' => $units,
            'roles' => $roles,
            'allPermissionKeys' => self::getAllPermissionKeys(),
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:150',
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'phone_number' => 'nullable|string|max:30',
            'role' => 'nullable|string',
            'role_id' => 'nullable|exists:roles,id',
            'room_id' => 'nullable|exists:rooms,id',
            'unit_id' => 'nullable|exists:rooms,id',
            'is_active' => 'nullable|boolean',
            'page_permissions' => 'nullable|array',
            'use_role_default' => 'nullable|boolean',
        ];

        if (!empty($request->password) || !empty($request->current_password)) {
            $rules['current_password'] = ['required', 'current_password'];
            $rules['password'] = ['required', 'string', 'min:6'];
        }

        $validated = $request->validate($rules, [
            'current_password.required' => 'Masukkan kata sandi Anda sendiri sebagai verifikasi pengaman.',
            'current_password.current_password' => 'Kata sandi login Anda saat ini tidak sesuai.',
            'password.required' => 'Masukkan kata sandi baru untuk pengguna ini.',
            'password.min' => 'Kata sandi baru minimal 6 karakter.',
        ]);

        if (!empty($validated['role_id'])) {
            $roleId = (int) $validated['role_id'];
            $validated['role'] = match ($roleId) {
                Role::ADMINISTRATOR => 'ADMINISTRATOR',
                Role::DIREKTUR => 'DIREKTUR',
                Role::KEPALA_BIDANG => 'KABID',
                Role::KEPALA_SEKSI => 'KASI',
                default => 'STAFF',
            };
        } else {
            $roleId = match ($validated['role'] ?? 'STAFF') {
                'ADMINISTRATOR', 'SUPERADMIN' => Role::ADMINISTRATOR,
                'DIREKTUR' => Role::DIREKTUR,
                'KABID' => Role::KEPALA_BIDANG,
                'KASI' => Role::KEPALA_SEKSI,
                default => Role::STAFF,
            };
            $validated['role_id'] = $roleId;
            $validated['role'] = $validated['role'] ?? 'STAFF';
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        unset($validated['current_password']);

        if ($request->boolean('use_role_default')) {
            $validated['page_permissions'] = null;
        }

        if (array_key_exists('unit_id', $validated) && !array_key_exists('room_id', $validated)) {
            $validated['room_id'] = $validated['unit_id'];
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Update page permissions specifically for a user.
     */
    public function updatePermissions(Request $request, User $user)
    {
        $validated = $request->validate([
            'page_permissions' => 'nullable|array',
            'page_permissions.*' => 'string',
            'use_role_default' => 'boolean',
        ]);

        if ($request->boolean('use_role_default')) {
            $user->update(['page_permissions' => null]);
        } else {
            $user->update(['page_permissions' => $validated['page_permissions'] ?? []]);
        }

        return redirect()->back()->with('success', 'Hak akses halaman pengguna berhasil diperbarui.');
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

        if ($user->is_active && !empty($user->phone_number)) {
            try {
                $user->load('room');
                $unitName = $user->room ? ($user->room->name . ' (' . $user->room->location_info . ')') : 'Pelayanan Rumah Sakit';
                $waMsg = "Halo *{$user->name}*,\n\n"
                    . "Akun Anda di sistem *SIPUAS* telah *DISETUJUI & DIAKTIFKAN* oleh Administrator.\n\n"
                    . "🏥 *Ruangan :* {$unitName}\n"
                    . "👤 *Username :* {$user->username}\n\n"
                    . "Silakan login menggunakan akun Pesu Peluh Anda dan pastikan melakukan Presensi dinas harian (Clock-In) saat bertugas.\n\n"
                    . "Salam hangat,\n_Tim Manajemen Pelayanan SIPUAS_";

                $channel = new WaGatewayChannel();
                $channel->send($user->phone_number, new class($waMsg) extends \Illuminate\Notifications\Notification {
                    public function __construct(public string $msg) {}
                    public function toWaGateway($notifiable) { return $this->msg; }
                });
            } catch (\Throwable $e) {
                // Silently log or ignore
            }
        }

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
