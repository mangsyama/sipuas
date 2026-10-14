<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportStaff;
use App\Models\Room;
use App\Models\Staff;
use App\Models\StaffKpiLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportAuditController extends Controller
{
    /**
     * Tampilkan daftar audit laporan masyarakat & verifikasi poin staf.
     */
    public function index(Request $request): Response
    {
        $search = trim($request->input('search', ''));
        $roomId = $request->input('room_id', 'ALL');
        $status = $request->input('status', 'ALL');
        $actionType = $request->input('action_type', 'ALL');
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 10;
        }

        $query = Report::query()
            ->with([
                'room:id,name,building_name,location_floor',
                'verifier:id,name',
                'reportStaff.hospitalStaff:id,name,nip,position,total_points',
                'reportStaff.user:id,name,nip,total_points',
            ])
            ->latest('id');

        // Filter Pencarian Teks (Tiket, Pelapor, Isi Aduan, Staf Terkait)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('reporter_name', 'like', "%{$search}%")
                    ->orWhere('isi_laporan', 'like', "%{$search}%")
                    ->orWhere('supervisor_notes', 'like', "%{$search}%")
                    ->orWhereHas('reportStaff.hospitalStaff', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('nip', 'like', "%{$search}%")
                            ->orWhere('position', 'like', "%{$search}%");
                    })
                    ->orWhereHas('reportStaff.user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('nip', 'like', "%{$search}%");
                    });
            });
        }

        // Filter Ruangan
        if ($roomId !== 'ALL' && !empty($roomId)) {
            if ($roomId === 'GLOBAL') {
                $query->whereNull('room_id');
            } else {
                $query->where('room_id', (int) $roomId);
            }
        }

        // Filter Status Verifikasi
        if ($status !== 'ALL' && !empty($status)) {
            if ($status === 'VERIFIED') {
                $query->where('status', 'VERIFIED');
            } elseif ($status === 'PENDING') {
                $query->whereIn('status', ['PENDING', 'OPEN', 'IN_PROGRESS', 'SUBMITTED']);
            } elseif ($status === 'REJECTED') {
                $query->where('status', 'REJECTED');
            }
        }

        // Filter Jenis Tindakan (Penambahan, Pemotongan, Netral, Dibatalkan)
        if ($actionType !== 'ALL' && !empty($actionType)) {
            if ($actionType === 'DIBATALKAN') {
                $query->where(function ($dq) {
                    $dq->where('resolution_notes', 'like', '%DIBATALKAN%')
                        ->orWhereHas('reportStaff', function ($rsq) {
                            $rsq->where('action_type', 'DIBATALKAN');
                        });
                });
            } else {
                $query->whereHas('reportStaff', function ($rsq) use ($actionType) {
                    $rsq->where('action_type', $actionType);
                });
            }
        }

        // Pagination
        $reports = $query->paginate($perPage)->withQueryString();

        // Ringkasan Statistik
        $totalReports = Report::count();
        $verifiedReports = Report::where('status', 'VERIFIED')->count();
        $praiseActions = ReportStaff::where('action_type', 'PENAMBAHAN')->count();
        $complaintActions = ReportStaff::where('action_type', 'PEMOTONGAN')->count();

        // Data Ruangan Aktif untuk Dropdown Filter Standar
        $rooms = Room::query()
            ->select('id', 'name', 'building_name', 'location_floor')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Data Staf Aktif untuk Pilihan Koreksi Staf
        $staffList = Staff::query()
            ->select('id', 'name', 'nip', 'position', 'total_points')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Kategori KPI Standar
        $kpiCategories = [
            'KERAMAHAN' => 'Keramahan & Komunikasi',
            'KEDISIPLINAN' => 'Kerapihan & Kedisiplinan',
            'SOP_PELAYANAN' => 'Kecepatan Respons & SOP Pelayanan',
            'INTEGRITAS' => 'Integritas & Kejujuran Pelayanan',
            'KUALITAS_MEDIS' => 'Ketepatan & Kualitas Tindakan Medis',
            'LAINNYA' => 'Penilaian Layanan Lainnya',
        ];

        return Inertia::render('ReportAudit/Index', [
            'reports' => $reports,
            'rooms' => $rooms,
            'staffList' => $staffList,
            'kpiCategories' => $kpiCategories,
            'stats' => [
                'total_reports' => $totalReports,
                'verified_reports' => $verifiedReports,
                'praise_actions' => $praiseActions,
                'complaint_actions' => $complaintActions,
            ],
            'filters' => [
                'search' => $search,
                'room_id' => $roomId,
                'status' => $status,
                'action_type' => $actionType,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Lakukan koreksi audit terhadap verifikasi penilaian staf pada laporan (mendukung multi-staf).
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $validated = $request->validate([
            'action_type' => 'required|string|in:PENAMBAHAN,PEMOTONGAN,NETRAL,DIBATALKAN',
            'points' => 'required|integer|min:0|max:100',
            'kpi_category' => 'nullable|string|max:100',
            'severity_level' => 'nullable|string|max:100',
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'integer',
            'staff_id' => 'nullable|integer',
        ], [
            'action_type.required' => 'Jenis tindakan verifikasi wajib dipilih.',
            'action_type.in' => 'Pilihan jenis tindakan tidak valid.',
            'points.required' => 'Nilai poin wajib ditentukan.',
            'points.min' => 'Nilai poin minimal 0.',
            'points.max' => 'Nilai poin maksimal 100.',
        ]);

        DB::transaction(function () use ($request, $validated, $id) {
            $report = Report::with(['reportStaff.hospitalStaff', 'reportStaff.user', 'room'])->findOrFail($id);

            $actionType = $validated['action_type'];
            $rawPoints = abs((int) $validated['points']);
            $newSignedPoints = match ($actionType) {
                'PENAMBAHAN' => $rawPoints,
                'PEMOTONGAN' => -$rawPoints,
                default => 0,
            };

            $kpiCategory = in_array($actionType, ['NETRAL', 'DIBATALKAN']) ? null : ($validated['kpi_category'] ?? null);
            $severityLevel = in_array($actionType, ['NETRAL', 'DIBATALKAN']) ? null : ($validated['severity_level'] ?? $report->verified_severity_level);
            $baseNote = $report->supervisor_notes ?: 'Verifikasi laporan aduan/apresiasi unit.';

            // 1. Kumpulkan ID Target Staf Baru (Array)
            $inputStaffIds = $validated['staff_ids'] ?? [];
            if (empty($inputStaffIds) && !empty($validated['staff_id'])) {
                $inputStaffIds = [(int) $validated['staff_id']];
            }
            $targetStaffIds = array_values(array_unique(array_filter(array_map('intval', (array) $inputStaffIds))));

            // Ambil semua record ReportStaff saat ini
            $existingReportStaff = $report->reportStaff()->get();
            $existingStaffMap = [];
            foreach ($existingReportStaff as $rs) {
                if ($rs->staff_id) {
                    $existingStaffMap[$rs->staff_id] = $rs;
                }
            }

            // Jika tindakan NETRAL atau DIBATALKAN: Revert semua staf yang ada menjadi 0
            if (in_array($actionType, ['NETRAL', 'DIBATALKAN'])) {
                foreach ($existingReportStaff as $rs) {
                    $oldPts = (int) $rs->points;
                    $oldAct = $rs->action_type;
                    $stf = $rs->staff_id ? Staff::find($rs->staff_id) : null;
                    $usr = $rs->user_id ? User::find($rs->user_id) : ($stf?->user_id ? User::find($stf->user_id) : null);

                    if ($stf && $oldPts !== 0) {
                        $stf->decrement('total_points', $oldPts);
                        if ($oldAct === 'PENAMBAHAN') {
                            $stf->decrement('praise_count');
                        } elseif ($oldAct === 'PEMOTONGAN') {
                            $stf->decrement('complaint_count');
                        }
                        $stf->update(['last_point_update_at' => Carbon::now()]);
                    }
                    if ($usr && $oldPts !== 0) {
                        $usr->decrement('total_points', $oldPts);
                        if ($oldAct === 'PENAMBAHAN') {
                            $usr->decrement('praise_count');
                        } elseif ($oldAct === 'PEMOTONGAN') {
                            $usr->decrement('complaint_count');
                        }
                        $usr->update(['last_point_update_at' => Carbon::now()]);
                    }

                    $rs->update([
                        'action_type' => $actionType,
                        'kpi_category' => null,
                        'severity_level' => null,
                        'points' => 0,
                    ]);

                    StaffKpiLog::where('report_id', $report->id)
                        ->where('staff_id', $rs->staff_id)
                        ->update([
                            'action_type' => $actionType,
                            'kpi_category' => null,
                            'severity_level' => null,
                            'points' => 0,
                            'note' => $baseNote,
                        ]);
                }
            } else {
                // Tindakan PENAMBAHAN atau PEMOTONGAN

                // A. Revert staf yang dicabut (ada di existing tapi tidak ada di target baru)
                foreach ($existingStaffMap as $oldStaffId => $oldRs) {
                    if (!in_array($oldStaffId, $targetStaffIds, true)) {
                        $oldPts = (int) $oldRs->points;
                        $oldAct = $oldRs->action_type;
                        $stf = Staff::find($oldStaffId);
                        $usr = $oldRs->user_id ? User::find($oldRs->user_id) : ($stf?->user_id ? User::find($stf->user_id) : null);

                        if ($stf && $oldPts !== 0) {
                            $stf->decrement('total_points', $oldPts);
                            if ($oldAct === 'PENAMBAHAN') {
                                $stf->decrement('praise_count');
                            } elseif ($oldAct === 'PEMOTONGAN') {
                                $stf->decrement('complaint_count');
                            }
                            $stf->update(['last_point_update_at' => Carbon::now()]);
                        }
                        if ($usr && $oldPts !== 0) {
                            $usr->decrement('total_points', $oldPts);
                            if ($oldAct === 'PENAMBAHAN') {
                                $usr->decrement('praise_count');
                            } elseif ($oldAct === 'PEMOTONGAN') {
                                $usr->decrement('complaint_count');
                            }
                            $usr->update(['last_point_update_at' => Carbon::now()]);
                        }

                        $oldRs->delete();
                        StaffKpiLog::where('report_id', $report->id)
                            ->where('staff_id', $oldStaffId)
                            ->delete();
                    }
                }

                // B. Terapkan pembaruan untuk semua target staf terpilih
                foreach ($targetStaffIds as $stfId) {
                    $stf = Staff::find($stfId);
                    if (!$stf) {
                        continue;
                    }

                    $usr = null;
                    if ($stf->user_id) {
                        $usr = User::find($stf->user_id);
                    } elseif (!empty($stf->nip)) {
                        $usr = User::where('nip', $stf->nip)->first();
                        if ($usr) {
                            $stf->update(['user_id' => $usr->id]);
                        }
                    }

                    if (isset($existingStaffMap[$stfId])) {
                        // Staf Sudah Ada Sebelumnya -> Hitung Delta Poin
                        $rs = $existingStaffMap[$stfId];
                        $oldPts = (int) $rs->points;
                        $oldAct = $rs->action_type;
                        $delta = $newSignedPoints - $oldPts;

                        if ($delta !== 0) {
                            $stf->increment('total_points', $delta);
                            if ($usr) {
                                $usr->increment('total_points', $delta);
                            }
                        }

                        // Koreksi counter praise/complaint jika tindakan berubah
                        if ($oldAct === 'PENAMBAHAN' && $actionType !== 'PENAMBAHAN') {
                            $stf->decrement('praise_count');
                            if ($usr) $usr->decrement('praise_count');
                        } elseif ($oldAct !== 'PENAMBAHAN' && $actionType === 'PENAMBAHAN') {
                            $stf->increment('praise_count');
                            if ($usr) $usr->increment('praise_count');
                        }

                        if ($oldAct === 'PEMOTONGAN' && $actionType !== 'PEMOTONGAN') {
                            $stf->decrement('complaint_count');
                            if ($usr) $usr->decrement('complaint_count');
                        } elseif ($oldAct !== 'PEMOTONGAN' && $actionType === 'PEMOTONGAN') {
                            $stf->increment('complaint_count');
                            if ($usr) $usr->increment('complaint_count');
                        }

                        $stf->update(['last_point_update_at' => Carbon::now()]);
                        if ($usr) $usr->update(['last_point_update_at' => Carbon::now()]);

                        $rs->update([
                            'action_type' => $actionType,
                            'kpi_category' => $kpiCategory,
                            'severity_level' => $severityLevel,
                            'points' => $newSignedPoints,
                        ]);

                        StaffKpiLog::updateOrCreate(
                            [
                                'report_id' => $report->id,
                                'staff_id' => $stf->id,
                            ],
                            [
                                'user_id' => $usr?->id,
                                'verified_by' => $report->verified_by ?? $request->user()?->id,
                                'action_type' => $actionType,
                                'kpi_category' => $kpiCategory,
                                'severity_level' => $severityLevel,
                                'points' => $newSignedPoints,
                                'note' => $baseNote,
                                'logged_at' => Carbon::now(),
                            ]
                        );
                    } else {
                        // Staf Baru Ditambahkan ke Laporan Ini
                        if ($newSignedPoints !== 0) {
                            $stf->increment('total_points', $newSignedPoints);
                            if ($usr) $usr->increment('total_points', $newSignedPoints);
                        }
                        if ($actionType === 'PENAMBAHAN') {
                            $stf->increment('praise_count');
                            if ($usr) $usr->increment('praise_count');
                        } elseif ($actionType === 'PEMOTONGAN') {
                            $stf->increment('complaint_count');
                            if ($usr) $usr->increment('complaint_count');
                        }
                        $stf->update(['last_point_update_at' => Carbon::now()]);
                        if ($usr) $usr->update(['last_point_update_at' => Carbon::now()]);

                        ReportStaff::create([
                            'report_id' => $report->id,
                            'staff_id' => $stf->id,
                            'user_id' => $usr?->id,
                            'action_type' => $actionType,
                            'kpi_category' => $kpiCategory,
                            'severity_level' => $severityLevel,
                            'points' => $newSignedPoints,
                        ]);

                        StaffKpiLog::updateOrCreate(
                            [
                                'report_id' => $report->id,
                                'staff_id' => $stf->id,
                            ],
                            [
                                'user_id' => $usr?->id,
                                'verified_by' => $report->verified_by ?? $request->user()?->id,
                                'action_type' => $actionType,
                                'kpi_category' => $kpiCategory,
                                'severity_level' => $severityLevel,
                                'points' => $newSignedPoints,
                                'note' => $baseNote,
                                'logged_at' => Carbon::now(),
                            ]
                        );
                    }
                }
            }

            // Perbarui data verifikasi laporan secara langsung dan bersih (tanpa mengubah/menambah catatan laporan)
            $report->update([
                'status' => 'VERIFIED',
                'verified_kpi_category' => $kpiCategory,
                'verified_severity_level' => $severityLevel,
                'resolution_notes' => $actionType === 'DIBATALKAN' ? 'DIBATALKAN' : $report->resolution_notes,
            ]);
        });

        return redirect()->back()->with('success', 'Penilaian verifikasi berhasil diperbarui.');
    }
}
