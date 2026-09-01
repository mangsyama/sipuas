<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaMessageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_phone',
        'message_type',
        'content',
        'status',
        'response_payload',
    ];
}
