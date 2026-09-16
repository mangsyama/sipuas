<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleAccessControl
{
    /**
     * Handle an incoming request and enforce strict module role separation.
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

        // Administrator RS selalu memiliki akses penuh ke seluruh modul
        if ($user->isAdministrator()) {
            return $next($request);
        }

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

        // Jika tidak berhak, arahkan kembali secara halus ke modul resmi masing-masing
        if ($user->isStaff()) {
            return redirect()->route('staff.attendance');
        }

        if ($userRoleId === Role::KEPALA_SEKSI || $userRoleName === 'KASI' || $userRoleName === 'KEPALA SEKSI') {
            return redirect()->route('kasi.dashboard');
        }

        if (in_array($userRoleId, [Role::KEPALA_BIDANG, Role::DIREKTUR]) || in_array($userRoleName, ['KABID', 'DIREKTUR', 'KEPALA BIDANG'])) {
            return redirect()->route('executive.dashboard');
        }

        return redirect()->route('dashboard');
    }
}
