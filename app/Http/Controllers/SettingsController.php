<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display application and user settings page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load(['roleRelation', 'unit']);

        return Inertia::render('UserSettings/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username ?? '-',
                'nip' => $user->nip ?? '-',
                'phone_number' => $user->phone_number ?? '-',
                'role' => $user->role,
                'role_id' => $user->role_id,
                'unit_name' => $user->unit ? $user->unit->name : 'Semua Unit (Global)',
                'system_notify_enabled' => (bool)$user->system_notify_enabled,
                'wa_notify_enabled' => (bool)$user->wa_notify_enabled,
                'is_active' => (bool)$user->is_active,
                'created_at' => $user->created_at ? $user->created_at->format('d M Y') : '-',
            ],
        ]);
    }

    /**
     * Update notification preference toggles.
     */
    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'system_notify_enabled' => 'required|boolean',
            'wa_notify_enabled' => 'required|boolean',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update([
            'system_notify_enabled' => $validated['system_notify_enabled'],
            'wa_notify_enabled' => $validated['wa_notify_enabled'],
        ]);

        return redirect()->back()->with('success', 'Preferensi notifikasi berhasil diperbarui.');
    }
}
