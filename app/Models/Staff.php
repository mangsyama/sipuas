<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\NormalizesTimestamps;

class Staff extends Model
{
    use HasFactory, NormalizesTimestamps;

    protected $table = 'staff';

    protected $fillable = [
        'name',
        'position',
        'nip',
        'phone_number',
        'is_active',
        'total_points',
        'praise_count',
        'complaint_count',
        'user_id',
        'last_point_update_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_points' => 'integer',
        'praise_count' => 'integer',
        'complaint_count' => 'integer',
        'last_point_update_at' => 'datetime',
    ];

    /**
     * Ruangan-ruangan tempat staf ditugaskan (Many-to-Many).
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_staff')->withTimestamps();
    }

    /**
     * Akun user sistem jika staf ini memiliki akses login (opsional).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Riwayat log KPI atas verifikasi laporan.
     */
    public function kpiLogs(): HasMany
    {
        return $this->hasMany(StaffKpiLog::class, 'staff_id');
    }

    /**
     * Riwayat keterlibatan dalam laporan.
     */
    public function reportStaff(): HasMany
    {
        return $this->hasMany(ReportStaff::class, 'staff_id');
    }
}
