<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleAccessControl
{
    /**
     * Map of route names or prefixes to permission keys.
     */
    protected array $routePermissionMap = [
        'dashboard' => ['dashboard'],
        
        // Staff
        'staff.attendance' => ['staff.attendance'],
        'attendance.status' => ['staff.attendance'],
        'attendance.check-in' => ['staff.attendance'],
        'attendance.check-out' => ['staff.attendance'],
        'staff.dashboard' => ['staff.dashboard'],
        
        // Kasi
        'kasi.dashboard' => ['kasi.dashboard'],
        'kasi.feed' => ['kasi.feed', 'kasi.verify'],
        'kasi.verify' => ['kasi.feed', 'kasi.verify'],
        'kasi.verify.process' => ['kasi.feed', 'kasi.verify'],
        'kasi.logbook' => ['kasi.logbook'],
        'kasi.reports' => ['reports.index', 'kasi.reports'],
        'kasi.reports.destroy' => ['reports.index', 'kasi.reports'],
        
        // Kabid / Executive
        'kabid.dashboard' => ['executive.dashboard', 'kabid.dashboard'],
        'executive.dashboard' => ['executive.dashboard', 'kabid.dashboard'],
        'kabid.kasi-responsiveness' => ['executive.kasi-responsiveness', 'kabid.kasi-responsiveness'],
        'executive.kasi-responsiveness' => ['executive.kasi-responsiveness', 'kabid.kasi-responsiveness'],
        'kabid.leaderboard' => ['executive.leaderboard', 'kabid.leaderboard'],
        'executive.leaderboard' => ['executive.leaderboard', 'kabid.leaderboard'],
        'kabid.reports' => ['reports.index', 'kabid.reports'],
        
        // Reports
        'reports.index' => ['reports.index', 'kasi.reports', 'kabid.reports'],
        'reports.export.pdf' => ['reports.index', 'kasi.reports', 'kabid.reports'],
        'reports.export.excel' => ['reports.index', 'kasi.reports', 'kabid.reports'],
        'reports.export.csv' => ['reports.index', 'kasi.reports', 'kabid.reports'],
        'reports.history' => ['reports.index'],
        'reports.show' => ['reports.index'],
        
        // Users (Master Data)
        'users.index' => ['users.index'],
        'users.store' => ['users.index'],
        'users.show' => ['users.index'],
        'users.edit' => ['users.index'],
        'users.update' => ['users.index'],
        'users.destroy' => ['users.index'],
        'users.toggle-status' => ['users.index'],
        'users.reset-password' => ['users.index'],
        'users.permissions.update' => ['users.index'],
        
        // Approvals
        'users.approvals' => ['users.approvals'],
        'users.approvals.show' => ['users.approvals'],
        'users.approvals.approve' => ['users.approvals'],
        'users.approvals.reject' => ['users.approvals'],
        
        // Rooms / Units
        'rooms.index' => ['rooms.index'],
        'rooms.store' => ['rooms.index'],
        'rooms.update' => ['rooms.index'],
        'rooms.destroy' => ['rooms.index'],
        'rooms.toggle-status' => ['rooms.index'],
        'units.index' => ['rooms.index'],
        'units.store' => ['rooms.index'],
        'units.update' => ['rooms.index'],
        'units.destroy' => ['rooms.index'],
        'units.toggle-status' => ['rooms.index'],

        // Staff Management
        'staff-management.index' => ['staff-management.index', 'rooms.index', 'users.index'],
        'staff-management.store' => ['staff-management.index', 'rooms.index', 'users.index'],
        'staff-management.update' => ['staff-management.index', 'rooms.index', 'users.index'],
        'staff-management.destroy' => ['staff-management.index', 'rooms.index', 'users.index'],
        'staff-management.toggle-status' => ['staff-management.index', 'rooms.index', 'users.index'],
        'staff.index' => ['staff-management.index', 'rooms.index', 'users.index'],
        
        // System Integrations
        'admin.ai-settings.index' => ['admin.ai-settings.index'],
        'admin.ai-settings.update' => ['admin.ai-settings.index'],
        'admin.ai-settings.test' => ['admin.ai-settings.index'],
        'admin.wa-gateway.index' => ['admin.wa-gateway.index'],
        'admin.wa-gateway.status' => ['admin.wa-gateway.index'],
        'admin.wa-gateway.logout' => ['admin.wa-gateway.index'],
        'admin.wa-gateway.test' => ['admin.wa-gateway.index'],
        'admin.qr-generator.index' => ['admin.qr-generator.index'],
        'admin.qr-code.index' => ['admin.qr-generator.index'],
        
        // Public Form Report
        'report.create' => ['report.create'],
    ];

    /**
     * Handle an incoming request and enforce strict module role separation or page permission override.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$allowedRoles
     */
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $routeName = $request->route() ? $request->route()->getName() : null;

        // 1. Cek apakah route ini terdaftar dalam map permission halaman
        if ($routeName && isset($this->routePermissionMap[$routeName])) {
            $requiredPerms = $this->routePermissionMap[$routeName];
            foreach ($requiredPerms as $perm) {
                if ($user->hasPageAccess($perm)) {
                    return $next($request);
                }
            }
            // Jika rute terdaftar dalam map permission tetapi hak aksesnya dicabut/tidak dimiliki (termasuk admin), maka tolak & redirect
            return $this->redirectUnauthorized($user);
        }

        // 2. Administrator dengan default role (tanpa custom override) memiliki akses penuh
        if ($user->isAdministrator() && $user->page_permissions === null) {
            return $next($request);
        }

        // 3. Cek kesesuaian berdasarkan role bawaan
        $userRoleId = (int) $user->role_id;
        $userRoleName = strtoupper($user->role ?? '');

        $isAllowed = false;
        foreach ($allowedRoles as $role) {
            $r = strtoupper(trim($role));
            if ($r === 'ADMIN' && $user->isAdministrator()) {
                $isAllowed = true;
                break;
            }
            if ($r === 'STAFF' && $user->isStaff()) {
                $isAllowed = true;
                break;
            }
            if ($r === 'KASI' && ($userRoleId === Role::KEPALA_SEKSI || $userRoleName === 'KASI' || $userRoleName === 'KEPALA SEKSI')) {
                $isAllowed = true;
                break;
            }
            if (in_array($r, ['KABID', 'DIREKTUR']) && in_array($userRoleId, [Role::KEPALA_BIDANG, Role::DIREKTUR])) {
                $isAllowed = true;
                break;
            }
        }

        if ($isAllowed) {
            return $next($request);
        }

        return $this->redirectUnauthorized($user);
    }

    /**
     * Redirect unauthorized user to their authorized module gracefully.
     */
    protected function redirectUnauthorized(\App\Models\User $user): Response
    {
        $userRoleId = (int) $user->role_id;
        $userRoleName = strtoupper($user->role ?? '');

        if ($user->hasPageAccess('dashboard')) {
            return redirect()->route('dashboard');
        }

        if ($user->hasPageAccess('staff.attendance') || $user->isStaff()) {
            return redirect()->route('staff.attendance');
        }

        if ($user->hasPageAccess('kasi.dashboard') || $userRoleId === Role::KEPALA_SEKSI || $userRoleName === 'KASI' || $userRoleName === 'KEPALA SEKSI') {
            return redirect()->route('kasi.dashboard');
        }

        if ($user->hasPageAccess('executive.dashboard') || in_array($userRoleId, [Role::KEPALA_BIDANG, Role::DIREKTUR]) || in_array($userRoleName, ['KABID', 'DIREKTUR', 'KEPALA BIDANG'])) {
            return redirect()->route('kabid.dashboard');
        }

        if ($user->hasPageAccess('users.index')) {
            return redirect()->route('users.index');
        }

        if ($user->hasPageAccess('rooms.index')) {
            return redirect()->route('rooms.index');
        }

        return redirect()->route('profile.edit');
    }
}
