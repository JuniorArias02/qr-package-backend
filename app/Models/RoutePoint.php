<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutePoint extends Model
{
    protected $fillable = [
        'route_id',
        'latitude',
        'longitude',
        'radius_km',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_km' => 'float',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
