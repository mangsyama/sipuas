<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffKpiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'report_id',
        'verified_by',
        'action_type',
        'points',
        'note',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'logged_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
