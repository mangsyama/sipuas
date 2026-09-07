<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'ticket_number',
        'room_id',
        'target_object',
        'isi_laporan',
        'ai_sentiment',
        'ai_category',
        'ai_score',
        'ai_confidence',
        'ai_metadata',
        'shift_info',
        'reporter_name',
        'reporter_phone',
        'is_anonymous',
        'status',
        'priority',
        'verified_by',
        'verified_at',
        'supervisor_notes',
        'resolution_notes',
        'resolved_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Report $report) {
            if (empty($report->uuid)) {
                $report->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'ai_metadata' => 'array',
            'ai_score' => 'integer',
            'is_anonymous' => 'boolean',
            'verified_at' => 'datetime',
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
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

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'report_staff', 'report_id', 'user_id')
                    ->withPivot('action_type', 'points')
                    ->withTimestamps();
    }

    public function kpiLogs(): HasMany
    {
        return $this->hasMany(StaffKpiLog::class, 'report_id');
    }

    /**
     * Generate unique ticket number formatted like LP-YYYY-MM-XXXX
     */
    public static function generateTicketNumber(): string
    {
        $prefix = 'LP-' . date('Y-m') . '-';
        $random = str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        while (self::where('ticket_number', $prefix . $random)->exists()) {
            $random = str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        return $prefix . $random;
    }
}
