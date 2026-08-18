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
        'address_id',
        'package_reference',
        'address',
        'zone',
        'status',
    ];

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }
}
