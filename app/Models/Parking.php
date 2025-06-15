<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parking extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'check_in_time',
        'check_out_time',
        'check_in_image',
        'check_out_image',
        'status',
        'is_check_out_confirmed',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'is_check_out_confirmed' => 'boolean',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user(): BelongsTo
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
