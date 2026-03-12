<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyBatch extends Model
{
    protected $fillable = ['daily_code', 'date'];

    protected $casts = [
        'date' => 'date',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
