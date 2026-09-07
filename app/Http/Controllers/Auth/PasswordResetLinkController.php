<?php

namespace App\Http\Controllers\Auth;

use App\Channels\WaGatewayChannel;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request via WhatsApp.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $input = trim((string) ($request->input('account') ?: $request->input('email')));

        if (empty($input)) {
            throw ValidationException::withMessages([
                'account' => ['Silakan masukkan Username, NIP, atau Nomor WhatsApp akun Anda.'],
            ]);
        }

        // Clean phone digits if searching by phone
        $digitsOnly = preg_replace('/[^0-9]/', '', $input);
        $phoneTrimmed = ltrim($digitsOnly, '0');

        $user = User::where('username', $input)
            ->orWhere('nip', $input)
            ->orWhere('email', $input)
            ->when(!empty($digitsOnly), function ($q) use ($digitsOnly, $phoneTrimmed) {
                $q->orWhere('phone_number', $digitsOnly)
                  ->orWhere('phone_number', 'like', "%{$phoneTrimmed}");
            })
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'account' => ['Akun tidak ditemukan. Pastikan Username, NIP, atau Nomor WhatsApp yang Anda masukkan sudah benar.'],
            ]);
        }

        if (empty($user->phone_number)) {
            throw ValidationException::withMessages([
                'account' => ['Akun ini belum memiliki nomor WhatsApp terdaftar. Silakan hubungi Administrator untuk mereset kata sandi Anda.'],
            ]);
        }

        // Generate standard Laravel password reset token
        $token = Password::createToken($user);
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        // Format masked phone for user confirmation display
        $rawPhone = $user->phone_number;
        $maskedPhone = strlen($rawPhone) > 6 
            ? substr($rawPhone, 0, 4) . '••••' . substr($rawPhone, -3)
            : $rawPhone;

        $waMsg = "Halo *{$user->name}*,\n\n"
            . "Kami menerima permintaan untuk mengatur ulang kata sandi akun *SIPUAS* Anda.\n\n"
            . "Silakan klik tautan resmi berikut untuk membuat kata sandi baru:\n"
            . "🔗 {$resetUrl}\n\n"
            . "⚠️ Tautan ini bersifat rahasia dan berlaku selama 60 menit. Abaikan pesan ini jika Anda tidak merasa melakukan permintaan ini.\n\n"
            . "Salam hangat,\n_Tim Manajemen Pelayanan SIPUAS_";

        try {
            $channel = new WaGatewayChannel();
            $channel->send($user->phone_number, new class($waMsg) extends Notification {
                public function __construct(public string $msg) {}
                public function toWaGateway($notifiable) { return $this->msg; }
            });
        } catch (\Throwable $e) {
            Log::error('Password reset WhatsApp failed: ' . $e->getMessage());
        }

        return back()->with('status', "Tautan atur ulang kata sandi telah dikirim ke WhatsApp Anda ({$maskedPhone}). Silakan periksa pesan masuk WhatsApp Anda.");
    }
}
