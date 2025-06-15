<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'license_plate',
        'image',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parkings(): HasMany
    {
        return $this->hasMany(Parking::class);
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn($image) => $image ? url('/storage/vehicles/' . $image) : null
        );
    }

    protected function licensePlate(): Attribute
    {
        return Attribute::make(
            set: fn($value) => strtoupper(trim($value)),
        );
    }
}
