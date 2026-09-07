<?php

namespace App\Models;

/**
 * Backward compatibility alias for Room model.
 */
class Unit extends Room
{
    protected $table = 'rooms';

    protected $appends = [
        'location_info',
        'code',
    ];

    public function getCodeAttribute(): string
    {
        return $this->location_info;
    }
}
