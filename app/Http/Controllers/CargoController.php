<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class CargoController extends Controller
{
    public function index()
    {
        return response()->json(
            Cargo::orderBy('nombre')->get()
        );
    }

    public function activos()
    {
        $cargos = Cache::remember('cargos_activos', 300, function () {
            return Cargo::activos()->orderBy('nombre')->get();
        });

        return response()->json($cargos);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:cargos',
            'descripcion' => 'nullable|string',
            'requisitos' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $cargo = Cargo::create($data);

        return response()->json([
            'message' => 'Cargo creado exitosamente',
            'cargo' => $cargo
        ], 201);
    }

    public function show(Cargo $cargo)
    {
        return response()->json($cargo);
    }

    public function update(Request $request, Cargo $cargo)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('cargos')->ignore($cargo->id)],
            'descripcion' => 'nullable|string',
            'requisitos' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $cargo->update($data);

        return response()->json([
            'message' => 'Cargo actualizado',
            'cargo' => $cargo
        ]);
    }

    public function destroy(Cargo $cargo)
    {
        if ($cargo->ofertas()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: tiene convocatorias asociadas'
            ], 422);
        }

        $cargo->delete();

        return response()->json([
            'message' => 'Cargo eliminado'
        ]);
    }

    public function toggleActivo(Cargo $cargo)
    {
        $cargo->update(['activo' => !$cargo->activo]);

        return response()->json([
            'message' => $cargo->activo ? 'Cargo activado' : 'Cargo desactivado',
            'cargo' => $cargo
        ]);
    }
}
