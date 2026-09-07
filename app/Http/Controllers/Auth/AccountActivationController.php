<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountActivationController extends Controller
{
    /**
     * Tampilkan halaman status dan form pengajuan aktivasi akun dari Pesu Peluh.
     */
    public function notice(Request $request): Response|RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user() ?: \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $user->load('room');

        $rooms = Room::where('is_active', true)
            ->orderBy('building_name')
            ->orderBy('name')
            ->get(['id', 'name', 'building_name', 'location_floor']);

        return Inertia::render('Auth/Activation', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'nip' => $user->nip,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'room_id' => $user->room_id,
                'room_name' => $user->room ? $user->room->name : null,
                'room_location' => $user->room ? $user->room->location_info : null,
                // Backward compatibility aliases
                'unit_id' => $user->room_id,
                'unit_name' => $user->room ? $user->room->name : null,
                'is_active' => (bool) $user->is_active,
                'has_requested' => !empty($user->room_id),
                'created_at' => $user->created_at ? $user->created_at->format('d M Y, H:i') : null,
            ],
            'rooms' => $rooms,
            'units' => $rooms, // Backward compatibility
            'status' => session('status'),
        ]);
    }

    /**
     * Simpan pilihan ruangan bertugas dan nomor WhatsApp untuk pengajuan verifikasi aktivasi.
     */
    public function requestActivation(Request $request): RedirectResponse
    {
        // Support either room_id or unit_id input
        $roomId = $request->input('room_id') ?: $request->input('unit_id');
        $request->merge(['room_id' => $roomId]);

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'phone_number' => 'required|string|min:8|max:20',
        ], [
            'room_id.required' => 'Silakan pilih Ruangan tempat Anda bertugas.',
            'room_id.exists' => 'Ruangan yang dipilih tidak valid.',
            'phone_number.required' => 'Nomor WhatsApp wajib diisi untuk pengiriman notifikasi persetujuan akun.',
            'phone_number.min' => 'Nomor WhatsApp minimal 8 digit.',
            'phone_number.max' => 'Nomor WhatsApp maksimal 20 digit.',
        ]);

        /** @var User|null $user */
        $user = $request->user() ?: \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $user->update([
            'room_id' => $validated['room_id'],
            'phone_number' => $validated['phone_number'],
        ]);

        return redirect()->route('activation.notice')->with('status', 'Permohonan aktivasi akun berhasil diajukan! Administrator akan memverifikasi dan mengaktifkan akun Anda.');
    }
}
