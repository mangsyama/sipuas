<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KabidController;
use App\Http\Controllers\KasiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WaGatewayController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return redirect()->route('login');
});

// Public Patient Routes (Guest Mode - Tanpa Login)
Route::get('/report', [ReportController::class, 'create'])->name('report.create');
Route::post('/report', [ReportController::class, 'store'])->name('report.store');
Route::get('/report/success', [ReportController::class, 'success'])->name('report.success');
Route::get('/report/track', [ReportController::class, 'track'])->name('report.track');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Modul Staf Pelayanan — Live Attendance & Personal KPI
    Route::get('/staff/attendance', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('staff.attendance');
    Route::get('/staff/dashboard', [\App\Http\Controllers\StaffDashboardController::class, 'index'])->name('staff.dashboard');

    // Modul Kepala Seksi (Kasi) — PRD System
    Route::get('/kasi/dashboard', [KasiController::class, 'dashboard'])->name('kasi.dashboard');
    Route::get('/kasi/verify/{id?}', [KasiController::class, 'verify'])->name('kasi.verify');
    Route::post('/kasi/verify/{id}/process', [KasiController::class, 'processVerification'])->name('kasi.verify.process');
    Route::get('/kasi/logbook', [KasiController::class, 'logbook'])->name('kasi.logbook');

    // Modul Kabid Pelayanan — PRD System
    Route::get('/executive/dashboard', [KabidController::class, 'dashboard'])->name('executive.dashboard');
    Route::get('/executive/kasi-responsiveness', [KabidController::class, 'kasiResponsiveness'])->name('executive.kasi-responsiveness');
    Route::get('/executive/leaderboard', [KabidController::class, 'leaderboard'])->name('executive.leaderboard');

    // Pengaturan Sistem & Preferensi Notifikasi
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/notifications', [\App\Http\Controllers\SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::patch('/profile/notifications', [\App\Http\Controllers\SettingsController::class, 'updateNotifications'])->name('profile.update-notifications');

    Route::get('/notifications', function () {
        return redirect()->route('dashboard');
    })->name('notifications.index');
    Route::post('/notifications/mark-as-read/{id?}', function () {
        return response()->json(['success' => true]);
    })->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-as-read', function () {
        return response()->json(['success' => true]);
    })->name('notifications.markAllAsRead');

    // WhatsApp Gateway Management
    Route::get('/wa-gateway', [WaGatewayController::class, 'index'])->name('admin.wa-gateway.index');
    Route::get('/wa-gateway/status', [WaGatewayController::class, 'status'])->name('admin.wa-gateway.status');
    Route::post('/wa-gateway/logout', [WaGatewayController::class, 'logout'])->name('admin.wa-gateway.logout');
    Route::post('/wa-gateway/test', [WaGatewayController::class, 'sendTest'])->name('admin.wa-gateway.test');

    // AI Integration Management (Google Gemini / Groq / OpenAI)
    Route::get('/ai-settings', [\App\Http\Controllers\AiSettingController::class, 'index'])->name('admin.ai-settings.index');
    Route::post('/ai-settings', [\App\Http\Controllers\AiSettingController::class, 'update'])->name('admin.ai-settings.update');
    Route::post('/ai-settings/test', [\App\Http\Controllers\AiSettingController::class, 'testConnection'])->name('admin.ai-settings.test');

    // Menu Route Placeholders
    Route::get('/services', fn () => redirect()->route('dashboard'))->name('services.index');
    Route::get('/services/medik', fn () => redirect()->route('dashboard'))->name('services.medik');
    Route::get('/services/non-medik', fn () => redirect()->route('dashboard'))->name('services.non-medik');
    Route::get('/services/units/{id}', fn () => redirect()->route('dashboard'))->name('services.units.show');

    Route::get('/technicians/position', fn () => redirect()->route('dashboard'))->name('technicians.position');
    Route::get('/technicians/radar', fn () => redirect()->route('dashboard'))->name('technicians.radar');

    Route::get('/reports', fn () => redirect()->route('dashboard'))->name('reports.index');
    Route::get('/reports/history', fn () => redirect()->route('dashboard'))->name('reports.history');
    Route::get('/reports/{id}', fn () => redirect()->route('dashboard'))->name('reports.show');

    Route::get('/reports-management', fn () => redirect()->route('dashboard'))->name('reports-management.index');
    Route::get('/reports-management/{id}', fn () => redirect()->route('dashboard'))->name('reports-management.show');

    Route::get('/service-management/rooms', fn () => redirect()->route('dashboard'))->name('service-management.rooms');
    Route::get('/service-management/categories', fn () => redirect()->route('dashboard'))->name('service-management.categories');
    Route::get('/service-management/supporting-units', fn () => redirect()->route('dashboard'))->name('service-management.supporting-units');
    Route::get('/service-management/working-hours', fn () => redirect()->route('dashboard'))->name('service-management.working-hours');

    // Master Data 1: User Management (Akun Sistem SIPUAS)
    Route::get('/users', [\App\Http\Controllers\UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [\App\Http\Controllers\UserManagementController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [\App\Http\Controllers\UserManagementController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [\App\Http\Controllers\UserManagementController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [\App\Http\Controllers\UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::patch('/users/{user}/toggle-status', [\App\Http\Controllers\UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{user}/reset-password', [\App\Http\Controllers\UserManagementController::class, 'resetPassword'])->name('users.reset-password');
    Route::patch('/users/{user}/permissions', [\App\Http\Controllers\UserManagementController::class, 'updatePermissions'])->name('users.permissions.update');
    
    // User Approvals (Persetujuan Pendaftaran Akun)
    Route::get('/users-approvals', [\App\Http\Controllers\UserApprovalController::class, 'index'])->name('users.approvals');
    Route::get('/users-approvals/{user}', [\App\Http\Controllers\UserApprovalController::class, 'show'])->name('users.approvals.show');
    Route::post('/users-approvals/{user}/approve', [\App\Http\Controllers\UserApprovalController::class, 'approve'])->name('users.approvals.approve');
    Route::delete('/users-approvals/{user}/reject', [\App\Http\Controllers\UserApprovalController::class, 'reject'])->name('users.approvals.reject');

    // Master Data 2: Hospital Units / Ruangan Pelayanan
    Route::get('/units', [\App\Http\Controllers\UnitManagementController::class, 'index'])->name('units.index');
    Route::post('/units', [\App\Http\Controllers\UnitManagementController::class, 'store'])->name('units.store');
    Route::put('/units/{unit}', [\App\Http\Controllers\UnitManagementController::class, 'update'])->name('units.update');
    Route::delete('/units/{unit}', [\App\Http\Controllers\UnitManagementController::class, 'destroy'])->name('units.destroy');
    Route::patch('/units/{unit}/toggle-status', [\App\Http\Controllers\UnitManagementController::class, 'toggleStatus'])->name('units.toggle-status');

    // Redirect legacy staff route to users
    Route::get('/staff', fn () => redirect()->route('users.index'))->name('staff.index');

    // Staff Attendance / Presensi Kehadiran Dinas
    Route::get('/attendance/status', [\App\Http\Controllers\AttendanceController::class, 'currentStatus'])->name('attendance.status');
    Route::post('/attendance/check-in', [\App\Http\Controllers\AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('/attendance/check-out', [\App\Http\Controllers\AttendanceController::class, 'checkOut'])->name('attendance.check-out');

    Route::get('/design-system', fn () => redirect()->route('dashboard'))->name('design-system.index');
    Route::get('/design-system/buttons-badges', fn () => redirect()->route('dashboard'))->name('design-system.buttons-badges');
    Route::get('/design-system/forms', fn () => redirect()->route('dashboard'))->name('design-system.forms');
    Route::get('/design-system/modals-alerts', fn () => redirect()->route('dashboard'))->name('design-system.modals-alerts');
    Route::get('/design-system/tables', fn () => redirect()->route('dashboard'))->name('design-system.tables');
    Route::get('/design-system/cards', fn () => redirect()->route('dashboard'))->name('design-system.cards');
    Route::get('/design-system/notifications', fn () => redirect()->route('dashboard'))->name('design-system.notifications');

    Route::get('/admin/qr-code', fn () => redirect()->route('dashboard'))->name('admin.qr-code.index');
    Route::get('/admin/qr-generator', fn () => redirect()->route('dashboard'))->name('admin.qr-generator.index');
});

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'id'])) {
        session()->put('locale', $locale);
        session()->save();
    }
    return redirect()->back();
})->name('lang.switch');

require __DIR__ . '/auth.php';
