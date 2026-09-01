<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'username',
        'nip',
        'phone_number',
        'password',
        'role',
        'unit_id',
        'is_active',
        'profile_photo_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function verifiedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'verified_by');
    }

    public function staffKpiLogs(): HasMany
    {
        return $this->hasMany(StaffKpiLog::class, 'verified_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'SUPERADMIN';
    }

    public function isKabid(): bool
    {
        return $this->role === 'KABID' || $this->role === 'SUPERADMIN';
    }

    public function isKasi(): bool
    {
        return $this->role === 'KASI' || $this->role === 'SUPERADMIN';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['SUPERADMIN', 'KABID', 'KASI']);
    }

    public function hasPageAccess(string $page): bool
    {
        return true;
    }

    public function isReportOnly(): bool
    {
        return false;
    }

    public function canDisposisi(): bool
    {
        return true;
    }
}
