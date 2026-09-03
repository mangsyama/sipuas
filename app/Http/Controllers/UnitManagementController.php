<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rule;

class UnitManagementController extends Controller
{
    /**
     * Display a listing of the hospital units.
     */
    public function index(Request $request): Response
    {
        $query = Unit::withCount(['users', 'staff', 'reports']);

        // Search Filter
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Category Filter
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        // Active Status Filter
        if ($request->has('status') && $request->query('status') !== '') {
            $query->where('is_active', $request->query('status') === '1');
        }

        $units = $query->orderBy('name')->get();

        // Calculate Stats
        $stats = [
            'total' => Unit::count(),
            'active' => Unit::where('is_active', true)->count(),
            'medik' => Unit::where('category', 'MEDIK')->count(),
            'non_medik' => Unit::where('category', 'NON_MEDIK')->count(),
        ];

        return Inertia::render('UnitManagement/Index', [
            'units' => $units,
            'stats' => $stats,
            'filters' => [
                'search' => $request->query('search', ''),
                'category' => $request->query('category', ''),
                'status' => $request->query('status', ''),
            ],
        ]);
    }

    /**
     * Store a newly created unit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:units,code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:MEDIK,NON_MEDIK'],
            'is_active' => ['boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $validated['is_active'] ?? true;

        Unit::create($validated);

        return redirect()->route('units.index')->with('success', 'Unit kerja rumah sakit berhasil ditambahkan.');
    }

    /**
     * Update the specified unit.
     */
    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('units', 'code')->ignore($unit->id)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:MEDIK,NON_MEDIK'],
            'is_active' => ['boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $unit->update($validated);

        return redirect()->route('units.index')->with('success', 'Data unit kerja berhasil diperbarui.');
    }

    /**
     * Remove the specified unit from storage.
     */
    public function destroy(Unit $unit)
    {
        // Check relationships
        if ($unit->reports()->exists() || $unit->staff()->exists() || $unit->users()->exists()) {
            return redirect()->route('units.index')->with('error', 'Unit ini tidak dapat dihapus karena memiliki riwayat laporan, staf, atau pengguna terkait. Nonaktifkan status unit sebagai gantinya.');
        }

        $unit->delete();

        return redirect()->route('units.index')->with('success', 'Unit kerja berhasil dihapus.');
    }

    /**
     * Toggle active status of the unit.
     */
    public function toggleStatus(Unit $unit)
    {
        $unit->is_active = !$unit->is_active;
        $unit->save();

        $statusText = $unit->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('units.index')->with('success', "Unit {$unit->name} berhasil {$statusText}.");
    }
}
