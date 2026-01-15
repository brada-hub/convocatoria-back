<?php

namespace App\Http\Controllers;

use App\Models\TipoDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TipoDocumentoController extends Controller
{
    /**
     * Listar todos los tipos de documento
     */
    public function index()
    {
        $tipos = TipoDocumento::orderBy('categoria')
            ->orderBy('orden')
            ->get();

        return response()->json($tipos);
    }

    /**
     * Listar solo los activos (para el frontend público)
     */
    public function activos()
    {
        $tipos = TipoDocumento::where('activo', true)
            ->orderBy('categoria')
            ->orderBy('orden')
            ->get();

        return response()->json($tipos);
    }

    /**
     * Obtener un tipo de documento
     */
    public function show(TipoDocumento $tipoDocumento)
    {
        return response()->json($tipoDocumento);
    }

    /**
     * Crear nuevo tipo de documento
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'campos' => 'nullable|array',
            'campos.*.nombre' => 'required_with:campos|string',
            'campos.*.label' => 'required_with:campos|string',
            'campos.*.tipo' => 'required_with:campos|in:text,number,year,date,select,file,textarea',
            'campos.*.obligatorio' => 'boolean',
            'campos.*.opciones' => 'nullable|array',
            'categoria' => 'required|in:personal,academico,laboral,capacitacion,produccion,reconocimiento,general',
            'permite_multiples' => 'boolean',
            'icono' => 'nullable|string|max:50',
            'orden' => 'integer',
            'activo' => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['nombre']);

        // Verificar slug único
        $count = TipoDocumento::where('slug', $data['slug'])->count();
        if ($count > 0) {
            $data['slug'] .= '-' . ($count + 1);
        }

        $tipo = TipoDocumento::create($data);

        return response()->json([
            'message' => 'Tipo de documento creado exitosamente',
            'tipo' => $tipo
        ], 201);
    }

    /**
     * Actualizar tipo de documento
     */
    public function update(Request $request, TipoDocumento $tipoDocumento)
    {
        $data = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'campos' => 'nullable|array',
            'campos.*.nombre' => 'required_with:campos|string',
            'campos.*.label' => 'required_with:campos|string',
            'campos.*.tipo' => 'required_with:campos|in:text,number,year,date,select,file,textarea',
            'campos.*.obligatorio' => 'boolean',
            'campos.*.opciones' => 'nullable|array',
            'categoria' => 'sometimes|in:personal,academico,laboral,capacitacion,produccion,reconocimiento,general',
            'permite_multiples' => 'boolean',
            'icono' => 'nullable|string|max:50',
            'orden' => 'integer',
            'activo' => 'boolean',
        ]);

        // Actualizar slug si cambió el nombre
        if (isset($data['nombre']) && $data['nombre'] !== $tipoDocumento->nombre) {
            $data['slug'] = Str::slug($data['nombre']);
            $count = TipoDocumento::where('slug', $data['slug'])
                ->where('id', '!=', $tipoDocumento->id)
                ->count();
            if ($count > 0) {
                $data['slug'] .= '-' . ($count + 1);
            }
        }

        $tipoDocumento->update($data);

        return response()->json([
            'message' => 'Tipo de documento actualizado',
            'tipo' => $tipoDocumento->fresh()
        ]);
    }

    /**
     * Eliminar tipo de documento
     */
    public function destroy(TipoDocumento $tipoDocumento)
    {
        // Verificar que no esté siendo usado por alguna convocatoria
        if ($tipoDocumento->convocatorias()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar: está siendo usado por convocatorias activas'
            ], 422);
        }

        // Verificar que no tenga documentos de postulantes
        if ($tipoDocumento->documentosPostulante()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar: tiene documentos de postulantes asociados'
            ], 422);
        }

        $tipoDocumento->delete();

        return response()->json([
            'message' => 'Tipo de documento eliminado'
        ]);
    }

    /**
     * Obtener tipos agrupados por categoría
     */
    public function porCategoria()
    {
        $tipos = TipoDocumento::where('activo', true)
            ->orderBy('orden')
            ->get()
            ->groupBy('categoria');

        return response()->json($tipos);
    }

    /**
     * Toggle activo/inactivo
     */
    public function toggleActivo(TipoDocumento $tipoDocumento)
    {
        $tipoDocumento->update(['activo' => !$tipoDocumento->activo]);

        return response()->json([
            'message' => $tipoDocumento->activo ? 'Tipo activado' : 'Tipo desactivado',
            'activo' => $tipoDocumento->activo
        ]);
    }

    /**
     * Reordenar tipos
     */
    public function reordenar(Request $request)
    {
        $request->validate([
            'orden' => 'required|array',
            'orden.*.id' => 'required|exists:tipos_documento,id',
            'orden.*.orden' => 'required|integer',
        ]);

        foreach ($request->orden as $item) {
            TipoDocumento::where('id', $item['id'])->update(['orden' => $item['orden']]);
        }

        return response()->json(['message' => 'Orden actualizado']);
    }
}
