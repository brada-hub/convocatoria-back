<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index()
    {
        return response()->json(Rol::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|unique:rols,nombre',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        $rol = Rol::create($validated);
        return response()->json($rol, 201);
    }

    public function show(Rol $rol)
    {
        return response()->json($rol);
    }

    public function update(Request $request, Rol $rol)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|unique:rols,nombre,' . $rol->id,
            'descripcion' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        $rol->update($validated);
        return response()->json($rol);
    }

    public function destroy(Rol $rol)
    {
        $rol->delete();
        return response()->json(null, 204);
    }

    public function toggleActivo(Rol $rol)
    {
        $rol->activo = !$rol->activo;
        $rol->save();
        return response()->json($rol);
    }
}
