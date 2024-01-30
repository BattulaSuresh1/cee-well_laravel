<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItems extends Model
{
    use HasFactory;

    public function product()
    {
        return $this->hasOne(Products::class,'id','product_id');
    }
    
    public function prescription()
    {
        return $this->hasOne(Prescription::class,'id','prescription_id');
    }

    public function lens()
    {
        return $this->hasOne(cartLens::class,'id','lenses_id');
    }

    public function measurements()
    {
        return $this->hasOne(cartMeasurements::class,'id','measurements_id');
    }
}
