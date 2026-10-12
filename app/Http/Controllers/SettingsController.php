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
        return Inertia::render('UserSettings/Index');
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
