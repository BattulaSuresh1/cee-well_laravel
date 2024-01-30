<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;
    public $timestamps = false;
    public function customerBranch()
    {
        return $this->hasOne('App\Models\CustomerBranch', 'billing_country_id');
        return $this->belongsTo(Country::class);
    }

}