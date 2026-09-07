<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    protected $fillable = [
        'user_id',
        'room_id',
        'unit_id',
        'duty_date',
        'shift_name',
        'check_in_at',
        'check_out_at',
        'status',
        'notes',
    ];

    protected function unitId(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => $this->room_id,
            set: fn ($value) => ['room_id' => $value],
        );
    }

    protected function casts(): array
    {
        return [
            'duty_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
