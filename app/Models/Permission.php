<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as Model;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'permission_group',
    ];
}