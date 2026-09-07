<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const ADMINISTRATOR = 1;
    public const DIREKTUR = 2;
    public const KEPALA_BIDANG = 3;
    public const KEPALA_SEKSI = 4;
    public const STAFF = 5;

    public $timestamps = false;
    protected $fillable = ['id', 'name', 'page_permissions'];

    protected function casts(): array
    {
        return [
            'page_permissions' => 'array',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
