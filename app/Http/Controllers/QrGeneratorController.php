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
            ->orderBy('building_name')
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => [
                'id' => (string) $r->id,
                'name' => $r->name,
                'building_name' => $r->building_name,
                'location_floor' => $r->location_floor,
                'location_info' => $r->location_info,
            ]);

        return Inertia::render('QrGenerator/Index', [
            'baseUrl' => url('/'),
            'rooms' => $rooms,
            'units' => $rooms,
        ]);
    }
}
