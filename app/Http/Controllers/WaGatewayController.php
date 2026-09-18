<?php

namespace App\Http\Controllers;

use App\Channels\WaGatewayChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class WaGatewayController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('WaGateway/Index', [
            'driver' => config('services.wa_gateway.driver', 'local'),
            'localUrl' => WaGatewayChannel::getGatewayUrl('/send'),
        ]);
    }

    public function status(Request $request)
    {
        $statusUrl = WaGatewayChannel::getGatewayUrl('/status');

        $data = [
            'status' => 'offline',
            'qr' => null,
            'user' => null,
            'message' => 'Server WA Gateway belum berjalan di ' . $statusUrl
        ];

        $secretKey = config('services.wa_gateway.secret_key');

        try {
            $client = Http::timeout(3)->withoutVerifying();
            if (!empty($secretKey)) {
                $client = $client->withHeaders(['X-Api-Key' => $secretKey]);
            }
            $response = $client->get($statusUrl);
            if ($response->successful()) {
                $data = $response->json();
            }
        } catch (\Throwable $e) {
            // Gateway server not running
        }

        if ($request->header('X-Inertia')) {
            return redirect()->route('admin.wa-gateway.index');
        }

        return response()->json($data);
    }

    public function logout(Request $request)
    {
        $logoutUrl = WaGatewayChannel::getGatewayUrl('/logout');
        $secretKey = config('services.wa_gateway.secret_key');

        try {
            $client = Http::timeout(4)->withoutVerifying();
            if (!empty($secretKey)) {
                $client = $client->withHeaders(['X-Api-Key' => $secretKey]);
            }
            $response = $client->post($logoutUrl);
            
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json($response->json());
            }

            return back()->with('success', 'Perangkat WhatsApp berhasil diputuskan.');
        } catch (\Throwable $e) {
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gagal terhubung ke server WA Gateway: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Gagal terhubung ke server WA Gateway: ' . $e->getMessage());
        }
    }

    public function sendTest(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|min:2',
        ]);

        try {
            $success = WaGatewayChannel::sendDirect($request->input('phone'), $request->input('message'));

            if ($success) {
                return back()->with('success', 'Pesan Uji Coba WhatsApp telah dikirimkan!');
            }
            return back()->with('error', 'Gagal mengirim pesan uji coba. Periksa apakah server WA Gateway aktif.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim pesan uji coba: ' . $e->getMessage());
        }
    }
}
