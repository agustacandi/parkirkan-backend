<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn($image) => $image ? url('/storage/broadcasts/' . $image) : null
        );
    }
}
