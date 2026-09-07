<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitManagementController extends Controller
{
    /**
     * Display a listing of the hospital rooms.
     */
    public function index(Request $request): Response
    {
        $query = Room::withCount(['users', 'staff', 'reports']);

        // Search Filter
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('building_name', 'like', "%{$search}%")
                  ->orWhere('location_floor', 'like', "%{$search}%");
            });
        }

        // Building Filter
        if ($building = $request->query('building')) {
            $query->where('building_name', $building);
        }

        // Active Status Filter
        if ($request->has('status') && $request->query('status') !== '') {
            $query->where('is_active', $request->query('status') === '1');
        }

        $rooms = $query->orderBy('building_name')->orderBy('name')->get();

        // Calculate Stats
        $stats = [
            'total' => Room::count(),
            'active' => Room::where('is_active', true)->count(),
            'buildings' => Room::whereNotNull('building_name')->distinct()->count('building_name'),
        ];

        // List of available buildings for filter
        $availableBuildings = Room::whereNotNull('building_name')
            ->distinct()
            ->orderBy('building_name')
            ->pluck('building_name');

        return Inertia::render('RoomManagement/Index', [
            'rooms' => $rooms,
            'units' => $rooms, // Backward compatibility
            'stats' => $stats,
            'buildings' => $availableBuildings,
            'filters' => [
                'search' => $request->query('search', ''),
                'building' => $request->query('building', ''),
                'category' => $request->query('building', ''), // Backward compatibility
                'status' => $request->query('status', ''),
            ],
        ]);
    }

    /**
     * Store a newly created room.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'building_name' => ['nullable', 'string', 'max:150'],
            'location_floor' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        Room::create($validated);

        return redirect()->back()->with('success', 'Ruangan rumah sakit berhasil ditambahkan.');
    }

    /**
     * Update the specified room.
     */
    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'building_name' => ['nullable', 'string', 'max:150'],
            'location_floor' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $room->update($validated);

        return redirect()->back()->with('success', 'Data ruangan berhasil diperbarui.');
    }

    /**
     * Remove the specified room from storage.
     */
    public function destroy($id)
    {
        $room = Room::findOrFail($id);

        // Check relationships
        if ($room->reports()->exists() || $room->staff()->exists() || $room->users()->exists()) {
            return redirect()->back()->with('error', 'Ruangan ini tidak dapat dihapus karena memiliki riwayat laporan, staf, atau pengguna terkait. Nonaktifkan status ruangan sebagai gantinya.');
        }

        $room->delete();

        return redirect()->back()->with('success', 'Ruangan berhasil dihapus.');
    }

    /**
     * Toggle active status of the room.
     */
    public function toggleStatus($id)
    {
        $room = Room::findOrFail($id);
        $room->is_active = !$room->is_active;
        $room->save();

        $statusText = $room->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Ruangan {$room->name} berhasil {$statusText}.");
    }
}
