<?php

namespace App\Http\Controllers;

use App\Models\StaffAttendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    /**
     * Display live attendance page for staff.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $today = Carbon::today();

        // Presensi hari ini: prioritaskan sesi aktif (ON_DUTY), jika tidak ada ambil yang paling baru hari ini
        $todayAttendance = StaffAttendance::where('user_id', $user->id)
            ->where('status', 'ON_DUTY')
            ->latest('check_in_at')
            ->first()
            ?: StaffAttendance::where('user_id', $user->id)
                ->whereDate('duty_date', $today)
                ->latest('check_in_at')
                ->first();

        $attendanceToday = $todayAttendance ? [
            'id' => $todayAttendance->id,
            'shift_name' => $todayAttendance->shift_name,
            'clock_in' => $todayAttendance->check_in_at ? $todayAttendance->check_in_at->format('H.i') : '-',
            'clock_out' => $todayAttendance->check_out_at ? $todayAttendance->check_out_at->format('H.i') : '-',
            'status' => $todayAttendance->status,
            'is_on_duty' => $todayAttendance->status === 'ON_DUTY',
        ] : null;

        // Riwayat presensi (urutkan dari yang paling baru)
        $recentAttendances = StaffAttendance::where('user_id', $user->id)
            ->orderByDesc('duty_date')
            ->orderByDesc('check_in_at')
            ->take(15)
            ->get()
            ->map(function ($att) {
                return [
                    'id' => $att->id,
                    'formatted_date' => $att->duty_date ? Carbon::parse($att->duty_date)->translatedFormat('d F Y') : ($att->check_in_at ? $att->check_in_at->translatedFormat('d F Y') : '-'),
                    'clock_in' => $att->check_in_at ? $att->check_in_at->format('H.i') : '-',
                    'clock_out' => $att->check_out_at ? $att->check_out_at->format('H.i') : '-',
                    'shift_name' => $att->shift_name,
                    'status' => $att->status,
                ];
            });

        return Inertia::render('Staff/Attendance', [
            'staff' => [
                'id' => $user->id,
                'name' => $user->name,
                'unit_name' => $user->unit ? $user->unit->name : 'Unit Pelayanan RS',
                'is_on_duty' => (bool) ($todayAttendance ? $todayAttendance->status === 'ON_DUTY' : $user->is_on_duty),
            ],
            'attendanceToday' => $attendanceToday,
            'recentAttendances' => $recentAttendances,
        ]);
    }

    /**
     * Get current active attendance status for logged-in user.
     */
    public function currentStatus(Request $request)
    {
        $user = $request->user();

        $activeAttendance = StaffAttendance::where('user_id', $user->id)
            ->where('status', 'ON_DUTY')
            ->latest('check_in_at')
            ->first();

        return response()->json([
            'is_on_duty' => (bool) ($activeAttendance ? true : $user->is_on_duty),
            'current_attendance' => $activeAttendance,
        ]);
    }

    /**
     * Clock in (Presensi Masuk).
     */
    public function checkIn(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'shift_name' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:255',
        ]);

        $shiftName = !empty($validated['shift_name']) ? $validated['shift_name'] : 'PRESENSI';
        $today = Carbon::today()->toDateString();
        $roomId = $user->room_id ?? $user->unit_id ?? \App\Models\Room::first()?->id ?? 1;

        // Close any dangling open attendances
        StaffAttendance::where('user_id', $user->id)
            ->where('status', 'ON_DUTY')
            ->update([
                'status' => 'COMPLETED',
                'check_out_at' => Carbon::now(),
            ]);

        $attendance = StaffAttendance::create([
            'user_id' => $user->id,
            'room_id' => $roomId,
            'unit_id' => $roomId,
            'duty_date' => $today,
            'shift_name' => $shiftName,
            'check_in_at' => Carbon::now(),
            'status' => 'ON_DUTY',
            'notes' => $validated['notes'] ?? null,
        ]);

        $user->update(['is_on_duty' => true]);

        return redirect()->back()->with('success', "Presensi masuk berhasil dicatat.");
    }

    /**
     * Clock out (Presensi Pulang).
     */
    public function checkOut(Request $request)
    {
        $user = $request->user();

        $attendance = StaffAttendance::where('user_id', $user->id)
            ->where('status', 'ON_DUTY')
            ->latest('check_in_at')
            ->first();

        if ($attendance) {
            $attendance->update([
                'check_out_at' => Carbon::now(),
                'status' => 'COMPLETED',
            ]);
        }

        $user->update(['is_on_duty' => false]);

        return redirect()->back()->with('success', 'Presensi selesai / clock out berhasil dicatat. Terima kasih atas dedikasi Anda.');
    }
}
