<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use App\Exports\StaffTemplateExport;
use App\Imports\StaffImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffManagementController extends Controller
{
    /**
     * Display a listing of hospital staff.
     */
    public function index(Request $request): Response
    {
        $query = Staff::with(['rooms' => function ($q) {
            $q->orderBy('name');
        }]);

        // Search Filter (Nama, NIP, Jabatan)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        // Filter Penempatan Ruangan (Many-to-Many)
        if ($roomId = $request->query('room_id')) {
            $query->whereHas('rooms', function ($q) use ($roomId) {
                $q->where('rooms.id', $roomId);
            });
        }

        // Filter Jabatan
        if ($position = $request->query('position')) {
            $query->where('position', $position);
        }

        // Filter Status Aktif
        if ($request->has('status') && $request->query('status') !== '' && $request->query('status') !== 'ALL') {
            $query->where('is_active', $request->query('status') === '1' || $request->query('status') === 'true');
        }

        $staff = $query->orderBy('name')->get();

        // Calculate Stats
        $stats = [
            'total' => Staff::count(),
            'active' => Staff::where('is_active', true)->count(),
            'rooms_with_staff' => \Illuminate\Support\Facades\DB::table('room_staff')->distinct()->count('room_id'),
            'positions_count' => Staff::whereNotNull('position')->distinct()->count('position'),
        ];

        // Seluruh Daftar Ruangan (termasuk nonaktif) untuk Multi-Select Form & Filter
        $rooms = Room::orderBy('name')
            ->get(['id', 'name', 'building_name', 'location_floor', 'is_active']);

        // Daftar Distinct Jabatan untuk Dropdown Filter
        $availablePositions = Staff::whereNotNull('position')
            ->where('position', '!=', '')
            ->distinct()
            ->orderBy('position')
            ->pluck('position');

        return Inertia::render('StaffManagement/Index', [
            'staff' => $staff,
            'rooms' => $rooms,
            'positions' => $availablePositions,
            'stats' => $stats,
            'filters' => [
                'search' => $request->query('search', ''),
                'room_id' => $request->query('room_id', ''),
                'position' => $request->query('position', ''),
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
            'name' => ['required', 'string', 'max:150'],
            'position' => ['required', 'string', 'max:100'],
            'nip' => ['required', 'string', 'regex:/^[0-9]+$/', 'size:18', 'unique:staff,nip'],
            'phone_number' => ['nullable', 'string', 'regex:/^[0-9]+$/', 'min:10', 'max:15'],
            'is_active' => ['boolean'],
            'room_ids' => ['nullable', 'array'],
            'room_ids.*' => ['integer', 'exists:rooms,id'],
        ], [
            'name.required' => 'Nama lengkap staf wajib diisi.',
            'position.required' => 'Jabatan / Profesi staf wajib diisi.',
            'nip.required' => 'NIP wajib diisi.',
            'nip.regex' => 'NIP hanya boleh berisi angka.',
            'nip.size' => 'NIP harus terdiri dari 18 digit angka.',
            'nip.unique' => 'NIP ini sudah terdaftar pada staf lain.',
            'phone_number.regex' => 'Nomor HP hanya boleh berisi angka.',
            'phone_number.min' => 'Nomor HP minimal 10 digit angka.',
            'phone_number.max' => 'Nomor HP maksimal 15 digit angka.',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['total_points'] = 100;

        $linkedUser = User::where('nip', $validated['nip'])->first();
        if ($linkedUser) {
            $validated['user_id'] = $linkedUser->id;
        }

        $staff = Staff::create($validated);

        if (!empty($validated['room_ids'])) {
            $staff->rooms()->sync($validated['room_ids']);
        }

        return redirect()->back()->with('success', "Staf {$staff->name} berhasil ditambahkan.");
    }

    /**
     * Update the specified staff member.
     */
    public function update(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'position' => ['required', 'string', 'max:100'],
            'nip' => ['required', 'string', 'regex:/^[0-9]+$/', 'size:18', 'unique:staff,nip,' . $staff->id],
            'phone_number' => ['nullable', 'string', 'regex:/^[0-9]+$/', 'min:10', 'max:15'],
            'is_active' => ['boolean'],
            'room_ids' => ['nullable', 'array'],
            'room_ids.*' => ['integer', 'exists:rooms,id'],
        ], [
            'name.required' => 'Nama lengkap staf wajib diisi.',
            'position.required' => 'Jabatan / Profesi staf wajib diisi.',
            'nip.required' => 'NIP wajib diisi.',
            'nip.regex' => 'NIP hanya boleh berisi angka.',
            'nip.size' => 'NIP harus terdiri dari 18 digit angka.',
            'nip.unique' => 'NIP ini sudah terdaftar pada staf lain.',
            'phone_number.regex' => 'Nomor HP hanya boleh berisi angka.',
            'phone_number.min' => 'Nomor HP minimal 10 digit angka.',
            'phone_number.max' => 'Nomor HP maksimal 15 digit angka.',
        ]);

        if (empty($staff->user_id)) {
            $linkedUser = User::where('nip', $validated['nip'])->first();
            if ($linkedUser) {
                $validated['user_id'] = $linkedUser->id;
            }
        }

        $staff->update($validated);

        // Sync penempatan ruangan
        $staff->rooms()->sync($validated['room_ids'] ?? []);

        return redirect()->back()->with('success', "Data staf {$staff->name} berhasil diperbarui.");
    }

    /**
     * Remove the specified staff member.
     */
    public function destroy($id)
    {
        $staff = Staff::findOrFail($id);

        // Jika staf pernah diverifikasi dalam laporan atau memiliki log KPI
        if ($staff->reportStaff()->exists() || $staff->kpiLogs()->exists()) {
            return redirect()->back()->with('error', "Staf {$staff->name} memiliki riwayat verifikasi laporan / KPI. Sebaiknya nonaktifkan status staf agar riwayat historis tetap terjaga.");
        }

        // Lepas relasi pivot ruangan
        $staff->rooms()->detach();
        $staff->delete();

        return redirect()->back()->with('success', "Staf {$staff->name} berhasil dihapus.");
    }

    /**
     * Toggle active status of the staff member.
     */
    public function toggleStatus($id)
    {
        $staff = Staff::findOrFail($id);
        $staff->is_active = !$staff->is_active;
        $staff->save();

        $statusText = $staff->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Staf {$staff->name} berhasil {$statusText}.");
    }

    /**
     * Download format template untuk import data staf (.xlsx atau .csv)
     */
    public function downloadTemplate(Request $request)
    {
        $format = strtolower($request->query('format', 'xlsx'));

        if ($format === 'csv') {
            return Excel::download(
                new StaffTemplateExport,
                'template_daftar_staf_rs.csv',
                \Maatwebsite\Excel\Excel::CSV,
                [
                    'Content-Type' => 'text/csv',
                ]
            );
        }

        return Excel::download(
            new StaffTemplateExport,
            'template_daftar_staf_rs.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    /**
     * Import batch data staf dari file Excel (.xlsx / .xls) atau CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [
            'file.required' => 'Silakan pilih file Excel (.xlsx) atau CSV yang akan diimpor.',
            'file.file' => 'File yang diunggah tidak valid.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau CSV (.csv).',
            'file.max' => 'Ukuran file maksimal adalah 10 MB.',
        ]);

        try {
            $import = new StaffImport;
            Excel::import($import, $request->file('file'));

            $msg = "Import selesai: {$import->createdCount} staf baru ditambahkan, {$import->updatedCount} data diperbarui.";

            if (!empty($import->errors)) {
                $errorCount = count($import->errors);
                $previewErrors = implode('; ', array_slice($import->errors, 0, 3));
                if ($errorCount > 3) {
                    $previewErrors .= " (+ " . ($errorCount - 3) . " baris lainnya)";
                }

                if ($import->createdCount === 0 && $import->updatedCount === 0) {
                    return redirect()->back()->with('error', "Import gagal. Tidak ada baris yang valid: {$previewErrors}");
                }

                return redirect()->back()->with('success', "{$msg} Namun terdapat catatan: {$previewErrors}");
            }

            if ($import->createdCount === 0 && $import->updatedCount === 0) {
                return redirect()->back()->with('error', 'Tidak ada data staf yang berhasil diimpor. Pastikan file tidak kosong dan sesuai template.');
            }

            return redirect()->back()->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses file import: ' . $e->getMessage());
        }
    }
}
