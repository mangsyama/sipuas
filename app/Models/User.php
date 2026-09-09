<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'username',
        'nip',
        'phone_number',
        'password',
        'role_id',
        'room_id',
        'unit_id', // Backward compatibility
        'is_active',
        'is_on_duty',
        'total_points',
        'praise_count',
        'complaint_count',
        'last_point_update_at',
        'page_permissions',
        'approved_by',
        'approved_at',
        'telegram_chat_id',
        'system_notify_enabled',
        'wa_notify_enabled',
        'profile_photo_path',
        'role', // Handled via mutator
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'role',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
            if (empty($user->role_id)) {
                $user->role_id = Role::STAFF;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_on_duty' => 'boolean',
            'system_notify_enabled' => 'boolean',
            'wa_notify_enabled' => 'boolean',
            'page_permissions' => 'array',
            'approved_at' => 'datetime',
            'last_point_update_at' => 'datetime',
            'role_id' => 'integer',
            'room_id' => 'integer',
            'unit_id' => 'integer',
            'total_points' => 'integer',
            'praise_count' => 'integer',
            'complaint_count' => 'integer',
        ];
    }

    /**
     * Role relationship to Role model.
     */
    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Accessor & Mutator for role string (e.g. 'ADMINISTRATOR', 'KABID', 'KASI', 'STAFF')
     * for seamless compatibility with frontend Inertia pages.
     */
    protected function role(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function () {
                return match ((int)$this->role_id) {
                    Role::ADMINISTRATOR => 'ADMINISTRATOR',
                    Role::DIREKTUR => 'DIREKTUR',
                    Role::KEPALA_BIDANG => 'KABID',
                    Role::KEPALA_SEKSI => 'KASI',
                    default => 'STAFF',
                };
            },
            set: function (?string $value) {
                if (!$value) return [];
                $roleName = strtoupper($value);
                $roleId = match ($roleName) {
                    'ADMINISTRATOR', 'SUPERADMIN' => Role::ADMINISTRATOR,
                    'DIREKTUR' => Role::DIREKTUR,
                    'KABID', 'KEPALA BIDANG' => Role::KEPALA_BIDANG,
                    'KASI', 'KEPALA SEKSI' => Role::KEPALA_SEKSI,
                    default => Role::STAFF,
                };
                return ['role_id' => $roleId];
            }
        );
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Backward-compatibility alias
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Backward-compatibility accessor & mutator for unit_id
     */
    protected function unitId(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => $this->room_id,
            set: fn ($value) => ['room_id' => $value],
        );
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function kpiLogs(): HasMany
    {
        return $this->hasMany(StaffKpiLog::class, 'user_id');
    }

    public function verifiedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'verified_by');
    }

    public function verifiedKpiLogs(): HasMany
    {
        return $this->hasMany(StaffKpiLog::class, 'verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reportStaff(): HasMany
    {
        return $this->hasMany(ReportStaff::class, 'user_id');
    }

    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(Report::class, 'report_staff', 'user_id', 'report_id')
            ->withPivot(['action_type', 'points'])
            ->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return (int)$this->role_id === Role::ADMINISTRATOR;
    }

    public function isAdministrator(): bool
    {
        return (int)$this->role_id === Role::ADMINISTRATOR;
    }

    public function isDirektur(): bool
    {
        return in_array((int)$this->role_id, [Role::ADMINISTRATOR, Role::DIREKTUR]);
    }

    public function isKabid(): bool
    {
        return in_array((int)$this->role_id, [Role::ADMINISTRATOR, Role::DIREKTUR, Role::KEPALA_BIDANG]);
    }

    public function isKasi(): bool
    {
        return in_array((int)$this->role_id, [Role::ADMINISTRATOR, Role::KEPALA_SEKSI]);
    }

    public function isAdmin(): bool
    {
        return in_array((int)$this->role_id, [Role::ADMINISTRATOR, Role::DIREKTUR, Role::KEPALA_BIDANG, Role::KEPALA_SEKSI]);
    }

    public function isStaff(): bool
    {
        return (int)$this->role_id === Role::STAFF;
    }

    protected ?array $memoizedPermissions = null;

    /**
     * Get effective page permissions for this user (custom override takes priority; falls back to role default).
     */
    public function getEffectivePermissions(): array
    {
        if ($this->memoizedPermissions !== null) {
            return $this->memoizedPermissions;
        }

        // 1. User specific custom permission override
        if ($this->page_permissions !== null && is_array($this->page_permissions)) {
            return $this->memoizedPermissions = $this->page_permissions;
        }

        // 2. Administrator has access to all pages
        if ($this->isAdministrator()) {
            return $this->memoizedPermissions = [
                'dashboard', 'staff.attendance', 'staff.dashboard', 'attendance.status',
                'kasi.dashboard', 'kasi.verify', 'kasi.logbook',
                'executive.dashboard', 'executive.kasi-responsiveness', 'executive.leaderboard',
                'reports.index',
                'units.index', 'users.approvals', 'users.index',
                'admin.ai-settings.index', 'admin.wa-gateway.index', 'admin.qr-generator.index', 'settings.index',
            ];
        }

        // 3. Fall back to role table defaults
        $role = $this->roleRelation ?? $this->load('roleRelation')->roleRelation;
        if ($role && is_array($role->page_permissions)) {
            return $this->memoizedPermissions = $role->page_permissions;
        }

        // 4. Default fallback by role_id
        return $this->memoizedPermissions = match ((int)$this->role_id) {
            Role::DIREKTUR, Role::KEPALA_BIDANG => [
                'dashboard', 'executive.dashboard', 'executive.kasi-responsiveness', 'executive.leaderboard',
                'kasi.dashboard', 'kasi.logbook', 'reports.index', 'units.index', 'settings.index',
            ],
            Role::KEPALA_SEKSI => [
                'dashboard', 'kasi.dashboard', 'kasi.verify', 'kasi.logbook',
                'staff.dashboard', 'reports.index', 'units.index', 'settings.index',
            ],
            default => [
                'staff.dashboard', 'attendance.status', 'settings.index',
            ],
        };
    }

    public function hasPageAccess(string $page): bool
    {
        return in_array($page, $this->getEffectivePermissions(), true);
    }

    public function isReportOnly(): bool
    {
        return false;
    }

    public function canDisposisi(): bool
    {
        return $this->isAdmin();
    }

    protected function uuid(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (?string $value) => $value ? strtolower($value) : null,
            set: fn (?string $value) => $value ? strtolower($value) : null,
        );
    }
}
