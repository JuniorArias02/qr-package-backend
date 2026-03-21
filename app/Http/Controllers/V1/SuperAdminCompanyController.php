<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CompanyDetailService;
use Illuminate\Http\Request;

class SuperAdminCompanyController extends Controller
{
    /**
     * Lista todas las empresas del sistema.
     * Solo accesible por super_admin.
     */
    public function index()
    {
        $companies = Company::withCount(['users', 'coordinators', 'drivers', 'deliveries'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status'  => 'success',
            'mensaje' => 'Empresas recuperadas correctamente',
            'objeto'  => $companies
        ]);
    }

    /**
     * Retorna una empresa específica con sus conteos.
     */
    public function show($id)
    {
        $company = Company::withCount(['users', 'coordinators', 'drivers', 'deliveries'])
            ->find($id);

        if (!$company) {
            return response()->json([
                'status'  => 'error',
                'mensaje' => 'Empresa no encontrada',
                'objeto'  => null
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'mensaje' => 'Empresa recuperada correctamente',
            'objeto'  => $company
        ]);
    }

    /**
     * Obtiene todos los detalles de una empresa (KPIs y usuarios por rol).
     */
    public function getDetails($id, CompanyDetailService $service)
    {
        try {
            $details = $service->getCompanyDetails($id);
            return response()->json([
                'status'  => 'success',
                'mensaje' => 'Detalle de empresa recuperado correctamente',
                'objeto'  => $details
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'mensaje' => 'Empresa no encontrada',
                'objeto'  => null
            ], 404);
        }
    }

    /**
     * Crea una nueva empresa.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'tax_id'  => 'nullable|string|unique:companies,tax_id|max:50',
            'address' => 'nullable|string|max:500',
            'phone'   => 'nullable|string|max:50',
            'active'  => 'nullable|boolean',
        ]);

        $company = Company::create([
            'name'    => $request->name,
            'tax_id'  => $request->tax_id,
            'address' => $request->address,
            'phone'   => $request->phone,
            'active'  => $request->input('active', true),
        ]);

        return response()->json([
            'status'  => 'success',
            'mensaje' => 'Empresa creada exitosamente',
            'objeto'  => $company
        ], 201);
    }

    /**
     * Actualiza los datos de una empresa.
     */
    public function update(Request $request, $id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'status'  => 'error',
                'mensaje' => 'Empresa no encontrada',
                'objeto'  => null
            ], 404);
        }

        $request->validate([
            'name'    => 'sometimes|string|max:255',
            'tax_id'  => 'sometimes|nullable|string|unique:companies,tax_id,' . $id . '|max:50',
            'address' => 'sometimes|nullable|string|max:500',
            'phone'   => 'sometimes|nullable|string|max:50',
            'active'  => 'sometimes|boolean',
        ]);

        if ($request->has('name'))    $company->name    = $request->name;
        if ($request->has('tax_id'))  $company->tax_id  = $request->tax_id;
        if ($request->has('address')) $company->address = $request->address;
        if ($request->has('phone'))   $company->phone   = $request->phone;
        if ($request->has('active'))  $company->active  = $request->active;

        $company->save();

        return response()->json([
            'status'  => 'success',
            'mensaje' => 'Empresa actualizada correctamente',
            'objeto'  => $company->fresh()
        ]);
    }

    /**
     * Alterna el estado activo/inactivo de una empresa.
     * Al desactivar, todos sus usuarios quedan bloqueados en el próximo login.
     */
    public function toggle($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'status'  => 'error',
                'mensaje' => 'Empresa no encontrada',
                'objeto'  => null
            ], 404);
        }

        $company->active = !$company->active;
        $company->save();

        $estado = $company->active ? 'activada' : 'desactivada';

        return response()->json([
            'status'  => 'success',
            'mensaje' => "Empresa {$estado} correctamente",
            'objeto'  => $company
        ]);
    }

    /**
     * Elimina una empresa (solo si no tiene usuarios activos asociados).
     */
    public function destroy($id)
    {
        $company = Company::withCount('users')->find($id);

        if (!$company) {
            return response()->json([
                'status'  => 'error',
                'mensaje' => 'Empresa no encontrada',
                'objeto'  => null
            ], 404);
        }

        if ($company->users_count > 0) {
            return response()->json([
                'status'  => 'error',
                'mensaje' => 'No se puede eliminar una empresa con usuarios registrados. Desactívela en su lugar.',
                'objeto'  => null
            ], 422);
        }

        $company->delete();

        return response()->json([
            'status'  => 'success',
            'mensaje' => 'Empresa eliminada correctamente',
            'objeto'  => null
        ]);
    }
}
