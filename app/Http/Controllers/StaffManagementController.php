<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rule;

class StaffManagementController extends Controller
{
    /**
     * Display a listing of staff members.
     */
    public function index(Request $request): Response
    {
        $query = Staff::with(['unit'])->withCount('kpiLogs');

        // Search Filter
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%");
            });
        }

        // Unit Filter
        if ($unitId = $request->query('unit_id')) {
            $query->where('unit_id', $unitId);
        }

        // Status Filter
        if ($request->has('status') && $request->query('status') !== '') {
            $query->where('is_active', $request->query('status') === '1');
        }

        $staff = $query->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        // Calculate Stats
        $stats = [
            'total' => Staff::count(),
            'active' => Staff::where('is_active', true)->count(),
            'praises' => (int) Staff::sum('praise_count'),
            'complaints' => (int) Staff::sum('complaint_count'),
            'top_score' => (int) (Staff::max('total_points') ?? 0),
        ];

        return Inertia::render('StaffManagement/Index', [
            'staffMembers' => $staff,
            'units' => $units,
            'stats' => $stats,
            'filters' => [
                'search' => $request->query('search', ''),
                'unit_id' => $request->query('unit_id', ''),
                'status' => $request->query('status', ''),
            ],
        ]);
    }

    /**
     * Store a newly created staff member.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:50', 'unique:staff,nip'],
            'role' => ['required', 'string', 'max:255'],
            'total_points' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        $validated['total_points'] = $validated['total_points'] ?? 100;
        $validated['praise_count'] = 0;
        $validated['complaint_count'] = 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        Staff::create($validated);

        return redirect()->route('staff.index')->with('success', 'Staf rumah sakit berhasil ditambahkan.');
    }

    /**
     * Update the specified staff member.
     */
    public function update(Request $request, Staff $staff)
    {
        $validated = $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('staff', 'nip')->ignore($staff->id)],
            'role' => ['required', 'string', 'max:255'],
            'total_points' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        $staff->update($validated);

        return redirect()->route('staff.index')->with('success', 'Data staf berhasil diperbarui.');
    }

    /**
     * Remove the specified staff member.
     */
    public function destroy(Staff $staff)
    {
        if ($staff->kpiLogs()->exists() || $staff->reports()->exists()) {
            return redirect()->route('staff.index')->with('error', 'Staf ini tidak dapat dihapus karena memiliki riwayat logbook KPI atau verifikasi laporan. Nonaktifkan status staf sebagai gantinya.');
        }

        $staff->delete();

        return redirect()->route('staff.index')->with('success', 'Data staf berhasil dihapus.');
    }

    /**
     * Toggle active status of the staff member.
     */
    public function toggleStatus(Staff $staff)
    {
        $staff->is_active = !$staff->is_active;
        $staff->save();

        $statusText = $staff->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('staff.index')->with('success', "Staf {$staff->name} berhasil {$statusText}.");
    }
}
