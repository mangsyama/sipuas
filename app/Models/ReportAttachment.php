<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReportAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'report_id',
        'file_path',
        'file_name',
        'file_type',
        'mime_type',
        'file_size_bytes',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReportAttachment $attachment) {
            if (empty($attachment->uuid)) {
                $attachment->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $appends = [
        'file_url',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}
