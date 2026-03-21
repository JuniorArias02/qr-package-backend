<?php

namespace App\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    /**
     * El "boot" del trait se ejecuta automáticamente al usar el modelo.
     */
    protected static function bootBelongsToCompany()
    {
        // 1. ASIGNACIÓN AUTOMÁTICA (Al crear registros)
        static::creating(function ($model) {
            // Si el usuario está logueado y el modelo no tiene company_id manual
            // y el usuario NO es super_admin (el super_admin suele asignar IDs manuales)
            if (Auth::check() && !$model->company_id) {
                $user = Auth::user();
                if ($user->company_id) {
                    $model->company_id = $user->company_id;
                }
            }
        });

        // 2. FILTRADO AUTOMÁTICO (Global Scope para lecturas)
        static::addGlobalScope('company', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                
                // Los usuarios con empresa solo ven lo de SU empresa.
                // El Super Admin (company_id NULL) ve TODO (no aplicamos filtro).
                if ($user->company_id) {
                    $builder->where('company_id', $user->company_id);
                }
            }
        });
    }

    /**
     * Relación con la Empresa.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
