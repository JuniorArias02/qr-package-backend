<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Traits\BelongsToCompany;

class Package extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id',
        'branch_id',
        'address_id',
        'package_reference',
        'address',
        'zone',
        'status',
        'latitude',
        'longitude',
        'is_geolocated',
    ];

    protected $casts = [
        'is_geolocated' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function geocodedAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
