<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WaGatewayController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;

Route::get('/', function () {
    return redirect()->route('login');
});

// Public Patient Routes (Guest Mode - Tanpa Login)
Route::get('/report', function (Request $request) {
    return Inertia::render('Report/Create', [
        'unitId' => $request->query('unit', ''),
    ]);
})->name('report.create');

Route::get('/report/success', function (Request $request) {
    return Inertia::render('Report/Success', [
        'id' => $request->query('id', 'LP-2026-08-001'),
    ]);
})->name('report.success');

Route::get('/dashboard', function (Request $request) {
    return Inertia::render('Dashboard/Index', [
        'user' => $request->user(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Modul Kepala Seksi (Kasi) — PRD System
    Route::get('/kasi/dashboard', function () {
        return Inertia::render('Kasi/Dashboard');
    })->name('kasi.dashboard');

    Route::get('/kasi/verify/{id?}', function ($id = 'LP-2026-08-001') {
        return Inertia::render('Kasi/Verify', ['id' => $id]);
    })->name('kasi.verify');

    Route::get('/kasi/logbook', function () {
        return Inertia::render('Kasi/Logbook');
    })->name('kasi.logbook');

    // Modul Kabid Pelayanan — PRD System
    Route::get('/executive/dashboard', function () {
        return Inertia::render('Kabid/Dashboard');
    })->name('executive.dashboard');

    Route::get('/executive/kasi-responsiveness', function () {
        return Inertia::render('Kabid/KasiResponsiveness');
    })->name('executive.kasi-responsiveness');

    Route::get('/executive/leaderboard', function () {
        return Inertia::render('Kabid/Leaderboard');
    })->name('executive.leaderboard');

    Route::get('/settings', function () {
        return redirect()->route('profile.edit');
    })->name('settings.index');

    Route::get('/notifications', function () {
        return redirect()->route('dashboard');
    })->name('notifications.index');

    // WhatsApp Gateway Management
    Route::get('/wa-gateway', [WaGatewayController::class, 'index'])->name('admin.wa-gateway.index');
    Route::get('/wa-gateway/status', [WaGatewayController::class, 'status'])->name('admin.wa-gateway.status');
    Route::post('/wa-gateway/logout', [WaGatewayController::class, 'logout'])->name('admin.wa-gateway.logout');
    Route::post('/wa-gateway/test', [WaGatewayController::class, 'sendTest'])->name('admin.wa-gateway.test');

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

    Route::get('/users', fn () => redirect()->route('dashboard'))->name('users.index');
    Route::get('/users/approvals', fn () => redirect()->route('dashboard'))->name('users.approvals');
    Route::get('/users/approvals/{id}', fn () => redirect()->route('dashboard'))->name('users.approvals.show');
    Route::get('/users/{id}', fn () => redirect()->route('dashboard'))->name('users.show');
    Route::get('/users/{id}/edit', fn () => redirect()->route('dashboard'))->name('users.edit');

    Route::get('/design-system', fn () => redirect()->route('dashboard'))->name('design-system.index');
    Route::get('/design-system/buttons-badges', fn () => redirect()->route('dashboard'))->name('design-system.buttons-badges');
    Route::get('/design-system/forms', fn () => redirect()->route('dashboard'))->name('design-system.forms');
    Route::get('/design-system/modals-alerts', fn () => redirect()->route('dashboard'))->name('design-system.modals-alerts');
    Route::get('/design-system/tables', fn () => redirect()->route('dashboard'))->name('design-system.tables');
    Route::get('/design-system/cards', fn () => redirect()->route('dashboard'))->name('design-system.cards');

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
