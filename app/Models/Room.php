<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'building_name',
        'location_floor',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'location_info',
    ];

    public function getLocationInfoAttribute(): string
    {
        $parts = array_filter([$this->building_name, $this->location_floor]);
        return count($parts) > 0 ? implode(' • ', $parts) : '-';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'room_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'room_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'room_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class, 'room_id');
    }
}
