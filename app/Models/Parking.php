<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Parking extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'check_in_time',
        'check_out_time',
        'check_in_image',
        'check_out_image',
        'status',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checkInImage(): Attribute
    {
        return Attribute::make(
            get: fn($image) => $image ? asset('storage/checkin/' . $image) : null,
        );
    }

    public function checkOutImage(): Attribute
    {
        return Attribute::make(
            get: fn($image) => $image ? asset('storage/checkout/' . $image) : null,
        );
    }
}
