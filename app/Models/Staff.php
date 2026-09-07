<?php

namespace App\Models;

/**
 * Backward-compatibility wrapper pointing to User model for staff entities.
 */
class Staff extends User
{
    protected $table = 'users';

    protected static function booted()
    {
        static::addGlobalScope('staff_only', function ($builder) {
            $builder->where('role_id', Role::STAFF);
        });
    }
}
