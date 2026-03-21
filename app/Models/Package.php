<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Traits\BelongsToCompany;

class Package extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id',
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
