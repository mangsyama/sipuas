<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load(['roleRelation', 'room']);

        return Inertia::render('UserProfile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => $user,
            'rooms' => \App\Models\Room::orderBy('name', 'asc')->get(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->except(['profile_photo']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->profile_photo === 'delete') {
            if ($user->profile_photo_path) {
                $oldPath = str_replace('/storage/', '', $user->profile_photo_path);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            $user->profile_photo_path = null;
        } elseif ($request->hasFile('profile_photo')) {
            $newPath = \App\Services\SecureFileUpload::saveUploadedFile($request->file('profile_photo'), 'profile_photos', 'profile_');
            if ($newPath) {
                if ($user->profile_photo_path) {
                    $oldPath = str_replace('/storage/', '', $user->profile_photo_path);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                }
                $user->profile_photo_path = $newPath;
            }
        } elseif ($request->filled('profile_photo') && str_starts_with($request->profile_photo, 'data:image')) {
            $newPath = \App\Services\SecureFileUpload::saveBase64($request->profile_photo, 'profile_photos', 'profile_');
            if ($newPath) {
                if ($user->profile_photo_path) {
                    $oldPath = str_replace('/storage/', '', $user->profile_photo_path);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                }
                $user->profile_photo_path = $newPath;
            }
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Data profil berhasil diperbarui.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
