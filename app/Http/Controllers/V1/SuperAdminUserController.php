<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Coordinator;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SuperAdminUserController extends Controller
{
    /**
     * Listado global de usuarios del sistema.
     */
    public function index(Request $request)
    {
        $query = User::with('company');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Usuarios globales recuperados',
            'objeto' => $users
        ]);
    }

    /**
     * Crear usuario de cualquier tipo (asociado o no a empresa).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6',
            'role'       => ['required', Rule::in(['super_admin', 'company_admin', 'coordinator', 'driver'])],
            'company_id' => 'nullable|exists:companies,id',
            'active'     => 'boolean',
        ]);

        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'role'       => $request->role,
            'company_id' => $request->company_id,
            'active'     => $request->input('active', true),
        ]);

        // Si es driver o coordinator asignamos el registro extra (si es necesario por lógica existente)
        if ($user->role === 'coordinator') {
            Coordinator::create(['user_id' => $user->id, 'company_id' => $user->company_id]);
        }
        if ($user->role === 'driver') {
            Driver::create(['user_id' => $user->id, 'company_id' => $user->company_id]);
        }

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Usuario creado exitosamente',
            'objeto' => $user->load('company')
        ], 201);
    }

    /**
     * Alternar estado de usuario.
     */
    public function toggle($id)
    {
        $user = User::findOrFail($id);
        $user->active = !$user->active;
        $user->save();

        $estado = $user->active ? 'activado' : 'desactivado';

        return response()->json([
            'status' => 'success',
            'mensaje' => "Usuario {$estado} correctamente",
            'objeto' => $user
        ]);
    }
}
