<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    protected $fillable = [
        'full_address',
        'normalized_address',
        'latitude',
        'longitude',
        'provider',
        'geocoded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'geocoded_at' => 'datetime',
    ];

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }
}
