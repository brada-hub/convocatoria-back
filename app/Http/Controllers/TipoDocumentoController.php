<?php

namespace App\Http\Controllers;

use App\Models\TipoDocumento;
use Illuminate\Http\Request;

class TipoDocumentoController extends Controller
{
    // Listar todos los tipos de documento activos
    public function index()
    {
        $tipos = TipoDocumento::activos()->get();
        return response()->json($tipos);
    }

    // Listar todos (incluyendo inactivos) - para admin
    public function all()
    {
        $tipos = TipoDocumento::orderBy('orden')->get();
        return response()->json($tipos);
    }

    // Crear nuevo tipo de documento
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipos_documento,nombre',
            'descripcion' => 'nullable|string|max:500',
            'icono' => 'nullable|string|max:50'
        ]);

        $maxOrden = TipoDocumento::max('orden') ?? 0;

        $tipo = TipoDocumento::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'icono' => $request->icono ?? 'description',
            'orden' => $maxOrden + 1,
            'activo' => true
        ]);

        return response()->json($tipo, 201);
    }

    // Actualizar tipo de documento
    public function update(Request $request, TipoDocumento $tipoDocumento)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipos_documento,nombre,' . $tipoDocumento->id,
            'descripcion' => 'nullable|string|max:500',
            'icono' => 'nullable|string|max:50',
            'activo' => 'boolean'
        ]);

        $tipoDocumento->update($request->only(['nombre', 'descripcion', 'icono', 'activo']));

        return response()->json($tipoDocumento);
    }

    // Activar/Desactivar tipo de documento
    public function toggle(TipoDocumento $tipoDocumento)
    {
        $tipoDocumento->update(['activo' => !$tipoDocumento->activo]);
        return response()->json($tipoDocumento);
    }

    // Eliminar tipo de documento (solo si no está en uso)
    public function destroy(TipoDocumento $tipoDocumento)
    {
        // Verificar si está en uso en alguna convocatoria
        if ($tipoDocumento->convocatorias()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar: Este tipo de documento está siendo usado por una o más convocatorias'
            ], 422);
        }

        $tipoDocumento->delete();
        return response()->json(['message' => 'Tipo de documento eliminado correctamente']);
    }
}
