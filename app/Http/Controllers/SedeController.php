<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class SedeController extends Controller
{
    public function index()
    {
        return response()->json(
            Sede::orderBy('nombre')->get()
        );
    }

    public function activas()
    {
        $sedes = Cache::remember('sedes_activas', 300, function () {
            return Sede::activos()->orderBy('nombre')->get();
        });

        return response()->json($sedes);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:sedes',
            'direccion' => 'nullable|string|max:500',
            'ciudad' => 'nullable|string|max:100',
            'activo' => 'boolean',
        ]);

        $sede = Sede::create($data);

        return response()->json([
            'message' => 'Sede creada exitosamente',
            'sede' => $sede
        ], 201);
    }

    public function show(Sede $sede)
    {
        return response()->json($sede);
    }

    public function update(Request $request, Sede $sede)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('sedes')->ignore($sede->id)],
            'direccion' => 'nullable|string|max:500',
            'ciudad' => 'nullable|string|max:100',
            'activo' => 'boolean',
        ]);

        $sede->update($data);

        return response()->json([
            'message' => 'Sede actualizada',
            'sede' => $sede
        ]);
    }

    public function destroy(Sede $sede)
    {
        // Verificar si tiene ofertas asociadas
        if ($sede->ofertas()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: tiene convocatorias asociadas'
            ], 422);
        }

        $sede->delete();

        return response()->json([
            'message' => 'Sede eliminada'
        ]);
    }

    public function toggleActivo(Sede $sede)
    {
        $sede->update(['activo' => !$sede->activo]);

        return response()->json([
            'message' => $sede->activo ? 'Sede activada' : 'Sede desactivada',
            'sede' => $sede
        ]);
    }
}
