<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'unit_id',
        'user_id',
        'nip',
        'name',
        'role',
        'total_points',
        'praise_count',
        'complaint_count',
        'is_active',
        'last_point_update_at',
    ];

    protected function casts(): array
    {
        return [
            'total_points' => 'integer',
            'praise_count' => 'integer',
            'complaint_count' => 'integer',
            'is_active' => 'boolean',
            'last_point_update_at' => 'datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(Report::class, 'report_staff')
                    ->withPivot('action_type', 'points')
                    ->withTimestamps();
    }

    public function kpiLogs(): HasMany
    {
        return $this->hasMany(StaffKpiLog::class)->orderBy('logged_at', 'desc');
    }
}
