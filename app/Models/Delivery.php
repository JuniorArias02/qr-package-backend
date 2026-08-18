<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\BelongsToCompany;

class Delivery extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id',
        'package_id',
        'driver_id',
        'coordinator_id',
        'daily_batch_id',
        'route_id',
        'delivery_order',
        'qr_url',
        'status',
        'observation',
        'assigned_at',
        'delivered_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Coordinator::class);
    }

    public function dailyBatch(): BelongsTo
    {
        return $this->belongsTo(DailyBatch::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
