<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Get all read notification IDs for current session / user.
     */
    public static function getReadIds(?Request $request = null, ?User $user = null): array
    {
        $request = $request ?: request();
        $user = $user ?: ($request ? $request->user() : null);

        if (!$user) {
            $sessionRead = ($request && $request->hasSession()) ? (array)$request->session()->get('read_notifications', []) : [];
            return array_values(array_unique(array_filter($sessionRead)));
        }

        // For authenticated users, database notifications table is the single source of truth
        if (Schema::hasTable('notifications')) {
            $cacheKey = "user_{$user->id}_read_notifications";
            return Cache::remember($cacheKey, 60, function () use ($user) {
                return DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $user->id)
                    ->whereNotNull('read_at')
                    ->get()
                    ->map(function ($row) {
                        $data = json_decode($row->data, true) ?? [];
                        return $data['notif_id'] ?? $row->id;
                    })
                    ->filter()
                    ->values()
                    ->toArray();
            });
        }

        $sessionRead = ($request && $request->hasSession()) ? (array)$request->session()->get('read_notifications', []) : [];
        return array_values(array_unique(array_filter($sessionRead)));
    }

    /**
     * Mark one or more notification IDs as read for the current session and user.
     */
    public static function markAsRead(string|array $ids, ?Request $request = null, ?User $user = null): array
    {
        $request = $request ?: request();
        $user = $user ?: ($request ? $request->user() : null);

        $idsToMerge = is_array($ids) ? $ids : [$ids];
        $idsToMerge = array_values(array_filter($idsToMerge));

        if (empty($idsToMerge)) {
            return self::getReadIds($request, $user);
        }

        if ($request && $request->hasSession()) {
            $sessionRead = (array)$request->session()->get('read_notifications', []);
            $allRead = array_values(array_unique(array_merge($sessionRead, $idsToMerge)));
            $request->session()->put('read_notifications', $allRead);
        }

        if ($user) {
            // Invalidate cache immediately
            Cache::forget("user_{$user->id}_read_notifications");

            if (Schema::hasTable('notifications')) {
                $now = now();
                foreach ($idsToMerge as $id) {
                    $isUuid = Str::isUuid($id);
                    $updated = DB::table('notifications')
                        ->where('notifiable_type', User::class)
                        ->where('notifiable_id', $user->id)
                        ->where(function ($q) use ($id, $isUuid) {
                            if ($isUuid) {
                                $q->where('id', $id)
                                  ->orWhere('data->notif_id', $id)
                                  ->orWhere('data->ticket_number', $id);
                            } else {
                                $q->where('data->notif_id', $id)
                                  ->orWhere('data->ticket_number', $id);
                            }
                        })
                        ->update(['read_at' => $now, 'updated_at' => $now]);

                    if ($updated === 0) {
                        DB::table('notifications')->insert([
                            'id' => (string)Str::uuid(),
                            'type' => 'App\Notifications\SystemNotification',
                            'notifiable_type' => User::class,
                            'notifiable_id' => $user->id,
                            'data' => json_encode(['notif_id' => $id]),
                            'read_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }

        return self::getReadIds($request, $user);
    }

    /**
     * Mark multiple notification IDs as read.
     */
    public static function markAllAsRead(array $ids, ?Request $request = null, ?User $user = null): array
    {
        return self::markAsRead($ids, $request, $user);
    }
}
