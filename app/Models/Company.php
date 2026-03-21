<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tax_id',
        'address',
        'phone',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function coordinators()
    {
        return $this->hasMany(Coordinator::class);
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function dailyBatches()
    {
        return $this->hasMany(DailyBatch::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }
}
