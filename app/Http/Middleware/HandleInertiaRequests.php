<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    protected static ?array $cachedTranslations = null;

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $permissions = $user ? $user->getEffectivePermissions() : [];
        $userRoomId = $user ? ($user->room_id ?? $user->unit_id) : null;

        // 1. Pending Approvals Count (Khusus Administrator / hak akses users.approvals - Hanya yang belum dibaca)
        $canApproveUsers = $user && ($user->isAdministrator() || $user->hasPageAccess('users.approvals'));
        $pendingApprovalsCount = function () use ($canApproveUsers, $request, $user) {
            if (!$canApproveUsers || ($user && $user->system_notify_enabled === false)) {
                return 0;
            }
            $allPendingUsers = \App\Models\User::where('is_active', false)->select(['id'])->get();
            $readNotificationIds = \App\Services\NotificationService::getReadIds($request, $user);

            return $allPendingUsers->filter(function ($u) use ($readNotificationIds) {
                return !in_array('user-' . $u->id, $readNotificationIds);
            })->count();
        };

        // 2. Pending Reports Count (Aduan Masuk yang perlu diverifikasi - Hanya yang belum dibaca)
        $canSeeReports = $user && (
            $user->isAdministrator() || 
            $user->isKasi() || 
            $user->isKabid() || 
            $user->isDirektur() || 
            $user->hasPageAccess('kasi.feed') || 
            $user->hasPageAccess('kasi.verify')
        );

        $pendingReportsCount = function () use ($canSeeReports, $userRoomId, $user, $request) {
            if (!$canSeeReports || ($user && $user->system_notify_enabled === false)) {
                return 0;
            }
            $reportsQuery = \App\Models\Report::where('status', 'PENDING');
            if ($userRoomId && !$user->isAdministrator() && !$user->isKabid() && !$user->isDirektur()) {
                $reportsQuery->where('room_id', $userRoomId);
            }
            $allPendingReports = $reportsQuery->select(['id'])->get();
            $readNotificationIds = \App\Services\NotificationService::getReadIds($request, $user);

            return $allPendingReports->filter(function ($rep) use ($readNotificationIds) {
                return !in_array('report-' . $rep->id, $readNotificationIds);
            })->count();
        };

        // 3. Lazy Evaluated Notifications (evaluates AFTER controller runs so auto-marked reads are captured)
        $memoizedNotifications = null;
        $resolveNotifications = function () use (&$memoizedNotifications, $request, $user, $canSeeReports, $canApproveUsers, $userRoomId) {
            if ($memoizedNotifications !== null) {
                return $memoizedNotifications;
            }

            // Jika preferensi notifikasi sistem dinonaktifkan oleh pengguna
            if ($user && $user->system_notify_enabled === false) {
                return $memoizedNotifications = [
                    'list' => [],
                    'unread_count' => 0,
                ];
            }

            $pendingReports = collect();
            if ($canSeeReports) {
                $reportsQuery = \App\Models\Report::where('status', 'PENDING');
                if ($userRoomId && !$user->isAdministrator() && !$user->isKabid() && !$user->isDirektur()) {
                    $reportsQuery->where('room_id', $userRoomId);
                }
                $pendingReports = $reportsQuery->with('room')->latest('created_at')->take(8)->get();
            }

            $pendingUsers = collect();
            if ($canApproveUsers) {
                $pendingUsers = \App\Models\User::where('is_active', false)
                    ->latest('created_at')
                    ->take(8)
                    ->get();
            }

            $readNotificationIds = \App\Services\NotificationService::getReadIds($request, $user);
            $notificationsList = collect();

            foreach ($pendingReports as $report) {
                $notifId = 'report-' . $report->id;
                $isRead = in_array($notifId, $readNotificationIds);
                if ($isRead) {
                    continue; // Langsung hilangkan dari list dropdown jika sudah terbaca
                }
                $roomName = $report->room ? $report->room->name : 'Unit Pelayanan';
                
                $notificationsList->push([
                    'id' => $notifId,
                    'type' => 'ticket',
                    'title' => 'Aduan: ' . $report->ticket_number,
                    'message' => $roomName . ' — ' . \Illuminate\Support\Str::limit($report->isi_laporan, 75),
                    'route' => route('kasi.verify', $report->ticket_number),
                    'read_at' => null,
                    'created_at' => $report->created_at ? $report->created_at->toIso8601String() : now()->toIso8601String(),
                    'time' => $report->created_at ? $report->created_at->diffForHumans() : 'Baru saja',
                    'priority' => $report->priority ?? 'NORMAL',
                ]);
            }

            foreach ($pendingUsers as $pUser) {
                $notifId = 'user-' . $pUser->id;
                $isRead = in_array($notifId, $readNotificationIds);
                if ($isRead) {
                    continue; // Langsung hilangkan dari list dropdown jika sudah terbaca
                }

                $notificationsList->push([
                    'id' => $notifId,
                    'type' => 'user',
                    'title' => 'Pendaftaran Pengguna Baru',
                    'message' => $pUser->name . ($pUser->nip ? ' (NIP: ' . $pUser->nip . ')' : '') . ' menunggu persetujuan akun.',
                    'route' => route('users.approvals'),
                    'read_at' => null,
                    'created_at' => $pUser->created_at ? $pUser->created_at->toIso8601String() : now()->toIso8601String(),
                    'time' => $pUser->created_at ? $pUser->created_at->diffForHumans() : 'Baru saja',
                    'priority' => 'NORMAL',
                ]);
            }

            $sorted = $notificationsList->sortByDesc('created_at')->values()->all();
            $unreadCount = count($sorted);

            return $memoizedNotifications = [
                'list' => $sorted,
                'unread_count' => $unreadCount,
            ];
        };

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'page_permissions' => $permissions,
                'pending_approvals_count' => fn () => $pendingApprovalsCount(),
                'pending_reports_count' => fn () => $pendingReportsCount(),
            ],
            'notifications' => fn () => $resolveNotifications()['list'],
            'unread_notifications_count' => fn () => $resolveNotifications()['unread_count'],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'pesupeluh_ticket_number' => fn () => $request->session()->get('pesupeluh_ticket_number'),
            ],
            'locale' => app()->getLocale(),
            'translations' => $this->getTranslations(),
        ];
    }

    /**
     * Get translations for the current locale.
     */
    protected function getTranslations(): array
    {
        $locale = app()->getLocale();
        if (isset(self::$cachedTranslations[$locale])) {
            return self::$cachedTranslations[$locale];
        }

        $file = base_path("lang/{$locale}.json");

        if (file_exists($file)) {
            return self::$cachedTranslations[$locale] = json_decode(file_get_contents($file), true) ?? [];
        }

        return self::$cachedTranslations[$locale] = [];
    }
}
