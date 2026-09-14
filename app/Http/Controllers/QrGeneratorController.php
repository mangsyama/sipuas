<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QrGeneratorController extends Controller
{
    /**
     * Display the QR Code Generator page.
     */
    public function index(Request $request): Response
    {
        $rooms = Room::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => [
                'id' => (string) $r->id,
                'name' => $r->name,
                'building_name' => $r->building_name,
                'location_floor' => $r->location_floor,
                'location_info' => $r->location_info,
            ]);

        $staffUsers = \App\Models\User::where('is_active', true)
            ->whereNotNull('name')
            ->with(['room:id,name,building_name,location_floor'])
            ->orderBy('name')
            ->get(['id', 'name', 'nip', 'room_id'])
            ->map(fn ($u) => [
                'id' => (string) $u->id,
                'name' => $u->name,
                'nip' => $u->nip,
                'room_id' => $u->room_id ? (string) $u->room_id : '',
                'room_name' => $u->room ? $u->room->name : '',
                'room_info' => $u->room ? ($u->room->name . ' (' . $u->room->location_info . ')') : 'Ruangan belum diatur',
            ]);

        return Inertia::render('QrGenerator/Index', [
            'baseUrl' => url('/'),
            'rooms' => $rooms,
            'units' => $rooms,
            'staffUsers' => $staffUsers,
        ]);
    }
}
