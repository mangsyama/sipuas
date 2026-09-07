<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use App\Models\Unit;
use App\Services\SecureFileUpload;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        $units = Room::query()
            ->where('is_active', true)
            ->orderBy('building_name')
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'code' => $r->location_info,
                'building_name' => $r->building_name,
                'location_floor' => $r->location_floor,
            ]);

        return Inertia::render('Auth/Register', [
            'units' => $units,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', 'unique:'.User::class],
            'nip' => ['required', 'string', 'regex:/^[0-9]+$/', 'max:18'],
            'unit_id' => ['nullable', 'exists:rooms,id'],
            'phone_number' => ['nullable', 'string', 'regex:/^[0-9]+$/', 'max:15'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'profile_photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'nip.required' => 'NIP wajib diisi.',
            'nip.regex' => 'NIP hanya boleh berisi angka.',
            'nip.max' => 'NIP maksimal 18 digit angka.',
            'phone_number.regex' => 'Nomor HP hanya boleh berisi angka.',
            'phone_number.max' => 'Nomor HP maksimal 15 digit angka.',
            'profile_photo.required' => 'Pasfoto diri wajib diunggah.',
            'profile_photo.image' => 'Berkas pasfoto harus berupa gambar.',
            'profile_photo.mimes' => 'Format pasfoto harus JPEG, PNG, JPG, atau WebP.',
            'profile_photo.max' => 'Ukuran pasfoto maksimal 10 MB.',
        ]);

        $photoPath = null;
        if ($request->hasFile('profile_photo')) {
            $photoPath = SecureFileUpload::saveUploadedFile($request->file('profile_photo'), 'profile_photos', 'profile_');
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'nip' => $request->nip,
            'unit_id' => $request->unit_id,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'profile_photo_path' => $photoPath,
            'password' => Hash::make($request->password),
            'role' => 'STAFF',
            'is_active' => false,
        ]);

        event(new Registered($user));

        return redirect()->route('login')->with('status', 'Pendaftaran berhasil! Akun Anda sedang menunggu proses verifikasi dan persetujuan dari Administrator.');
    }
}
