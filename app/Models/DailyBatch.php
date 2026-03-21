<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\BelongsToCompany;

class DailyBatch extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id',
        'daily_code',
        'date'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
