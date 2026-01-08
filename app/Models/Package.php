<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'referencia_qr',
        'direccion',
        'nombre_receptor',
        'telefono_receptor',
        'estado',
        'user_id_asignado',
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'user_id_asignado');
    }

    public function scans()
    {
        return $this->hasMany(Scan::class);
    }
}
