<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Package extends Model
{
    protected $fillable = [
        'package_reference',
        'address',
        'zone',
        'status',
    ];

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }
}
