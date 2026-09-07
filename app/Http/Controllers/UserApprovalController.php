<?php

namespace App\Http\Controllers;

use App\Channels\WaGatewayChannel;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
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

        $query = User::with('room')
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
            $query->where(function ($q) use ($unitFilter) {
                $q->where('room_id', $unitFilter)
                  ->orWhere('unit_id', $unitFilter);
            });
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
                'room_id' => $u->room_id,
                'unit_id' => $u->room_id,
                'unit_name' => $u->room ? $u->room->name : 'Belum Ditentukan',
                'room_name' => $u->room ? $u->room->name : 'Belum Ditentukan',
                'room_location' => $u->room ? $u->room->location_info : '-',
                'is_active' => (bool) $u->is_active,
                'created_at' => $u->created_at ? $u->created_at->format('d M Y, H:i') : '-',
                'created_at_human' => $u->created_at ? $u->created_at->diffForHumans() : '-',
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
        $user->load('room');
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
                'role_id' => $user->role_id ?? Role::STAFF,
                'room_id' => $user->room_id,
                'unit_id' => $user->room_id,
                'unit_name' => $user->room ? $user->room->name : 'Belum Ditentukan',
                'room_name' => $user->room ? $user->room->name : 'Belum Ditentukan',
                'room_location' => $user->room ? $user->room->location_info : '-',
                'is_active' => (bool) $user->is_active,
                'page_permissions' => $user->page_permissions,
                'created_at' => $user->created_at ? $user->created_at->format('d M Y, H:i') : '-',
                'created_at_human' => $user->created_at ? $user->created_at->diffForHumans() : '-',
            ],
            'units' => $units,
            'roles' => $roles,
            'allPermissionKeys' => UserManagementController::getAllPermissionKeys(),
        ]);
    }

    /**
     * Approve user registration.
     */
    public function approve(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'nullable|string',
            'role_id' => 'nullable|exists:roles,id',
            'room_id' => 'nullable|exists:rooms,id',
            'unit_id' => 'nullable|exists:rooms,id',
            'page_permissions' => 'nullable|array',
        ]);

        if (!empty($validated['role_id'])) {
            $roleId = (int) $validated['role_id'];
            $roleStr = match ($roleId) {
                Role::ADMINISTRATOR => 'ADMINISTRATOR',
                Role::DIREKTUR => 'DIREKTUR',
                Role::KEPALA_BIDANG => 'KABID',
                Role::KEPALA_SEKSI => 'KASI',
                default => 'STAFF',
            };
        } else {
            $roleStr = $validated['role'] ?? 'STAFF';
            $roleId = match ($roleStr) {
                'ADMINISTRATOR', 'SUPERADMIN' => Role::ADMINISTRATOR,
                'DIREKTUR' => Role::DIREKTUR,
                'KABID' => Role::KEPALA_BIDANG,
                'KASI' => Role::KEPALA_SEKSI,
                default => Role::STAFF,
            };
        }

        $roomId = $validated['room_id'] ?? $validated['unit_id'] ?? null;

        $user->update([
            'role' => $roleStr,
            'role_id' => $roleId,
            'room_id' => $roomId ?: null,
            'unit_id' => $roomId ?: null,
            'page_permissions' => !empty($validated['page_permissions']) ? $validated['page_permissions'] : null,
            'is_active' => true,
            'approved_by' => $request->user() ? $request->user()->id : null,
            'approved_at' => Carbon::now(),
        ]);

        // Auto-send WhatsApp activation confirmation if phone number is present
        if (!empty($user->phone_number)) {
            try {
                $user->load('room');
                $unitName = $user->room ? ($user->room->name . ' (' . $user->room->location_info . ')') : 'Pelayanan Rumah Sakit';
                $waMsg = "Halo Bapak/Ibu *{$user->name}*,\n\n"
                    . "Akun Anda di sistem *SIPUAS* telah *DISETUJUI & DIAKTIFKAN* oleh Administrator.\n\n"
                    . "• Penempatan Ruangan : {$unitName}\n"
                    . "• Role Sistem        : {$roleStr}\n"
                    . "• Username           : {$user->username}\n\n"
                    . "Silakan login menggunakan akun Pesu Peluh Anda dan pastikan melakukan absensi dinas harian (Check-In) saat bertugas.\n\n"
                    . "Salam hangat,\n_Tim Manajemen Pelayanan SIPUAS_";

                $channel = new WaGatewayChannel();
                $channel->send($user->phone_number, new class($waMsg) extends \Illuminate\Notifications\Notification {
                    public function __construct(public string $msg) {}
                    public function toWaGateway($notifiable) { return $this->msg; }
                });
            } catch (\Throwable $e) {
                // Silently log or ignore if WA gateway microservice is offline
            }
        }

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
