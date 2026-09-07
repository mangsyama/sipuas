<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginInput = trim($this->input('username'));
        $password = (string) $this->input('password');

        // 1. Find user in SIPUAS by username, nip, or email (including soft deleted)
        $user = \App\Models\User::withTrashed()
            ->where(function ($q) use ($loginInput) {
                $q->where('username', $loginInput)
                  ->orWhere('email', $loginInput)
                  ->orWhere('nip', $loginInput);
            })
            ->first();

        if ($user && $user->trashed()) {
            $user->restore();
        }

        // 2. JIT Cross-Check ke Database Pesupeluh jika user belum ada di SIPUAS
        if (!$user) {
            try {
                $pesupeluhUser = \Illuminate\Support\Facades\DB::connection('pesupeluh')
                    ->table('users')
                    ->where(function ($q) use ($loginInput) {
                        $q->where('username', $loginInput)
                          ->orWhere('email', $loginInput)
                          ->orWhere('nip', $loginInput);
                    })
                    ->first();

                if ($pesupeluhUser && \Illuminate\Support\Facades\Hash::check($password, $pesupeluhUser->password)) {
                    // Password Pesupeluh valid! Buat akun user di SIPUAS (pending approval)
                    $user = \App\Models\User::create([
                        'uuid' => $pesupeluhUser->uuid ?? (string) \Illuminate\Support\Str::uuid(),
                        'name' => $pesupeluhUser->name,
                        'username' => $pesupeluhUser->username,
                        'nip' => $pesupeluhUser->nip,
                        'email' => $pesupeluhUser->email,
                        'phone_number' => $pesupeluhUser->phone_number,
                        'room_id' => $pesupeluhUser->room_id ?? null,
                        'telegram_chat_id' => $pesupeluhUser->telegram_chat_id ?? null,
                        'password' => $pesupeluhUser->password, // Hash Bcrypt langsung disalin
                        'role_id' => \App\Models\Role::STAFF,
                        'total_points' => 100,
                        'praise_count' => 0,
                        'complaint_count' => 0,
                        'is_active' => false, // Menunggu aktivasi & approval administrator
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Pesupeluh JIT Auth Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
        } else {
            // User sudah ada di SIPUAS: cek password lokal
            if (!\Illuminate\Support\Facades\Hash::check($password, $user->password)) {
                // Jika password lokal tidak cocok, cek apakah password diperbarui di Pesupeluh
                try {
                    $pesupeluhUser = \Illuminate\Support\Facades\DB::connection('pesupeluh')
                        ->table('users')
                        ->where(function ($q) use ($user, $loginInput) {
                            $q->where('username', $user->username)
                              ->orWhere('email', $user->email)
                              ->orWhere('nip', $user->nip)
                              ->orWhere('username', $loginInput);
                        })
                        ->first();

                    if ($pesupeluhUser && \Illuminate\Support\Facades\Hash::check($password, $pesupeluhUser->password)) {
                        // Sinkronisasi password baru dari Pesupeluh ke SIPUAS
                        $user->password = $pesupeluhUser->password;
                        $user->save();
                    } else {
                        RateLimiter::hit($this->throttleKey());
                        throw ValidationException::withMessages([
                            'username' => trans('auth.failed'),
                        ]);
                    }
                } catch (ValidationException $ve) {
                    throw $ve;
                } catch (\Throwable $e) {
                    RateLimiter::hit($this->throttleKey());
                    throw ValidationException::withMessages([
                        'username' => trans('auth.failed'),
                    ]);
                }
            }
        }

        // 3. Jika user tidak ditemukan atau password tidak cocok
        if (!$user) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        // 4. Login-kan user (baik aktif maupun belum aktif).
        // Akun yang belum aktif akan diarahkan ke halaman aktivasi oleh middleware / controller.
        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
