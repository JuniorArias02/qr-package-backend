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
        'branch_id',
        'daily_code',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the company that owns the daily batch.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch that owns the daily batch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
