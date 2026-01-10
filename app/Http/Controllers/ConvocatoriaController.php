<?php

namespace App\Http\Controllers;

use App\Models\Convocatoria;
use App\Models\Oferta;
use App\Models\Postulante;
use App\Models\Postulacion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConvocatoriaController extends Controller
{
    // ==================== RUTAS PÚBLICAS ====================

    /**
     * Listar convocatorias abiertas con sus ofertas
     */
    public function abiertas()
    {
        $convocatorias = Convocatoria::abiertas()
            ->with(['ofertas' => function ($query) {
                $query->activos()->with(['sede', 'cargo']);
            }])
            ->orderBy('fecha_cierre')
            ->get();

        return response()->json($convocatorias);
    }

    /**
     * Obtener detalle de convocatoria por slug
     */
    public function porSlug($slug)
    {
        $convocatoria = Convocatoria::where('slug', $slug)
            ->with(['ofertas' => function ($query) {
                $query->activos()->with(['sede', 'cargo']);
            }, 'documentosRequeridos'])
            ->firstOrFail();

        // Agrupar ofertas por sede para el frontend
        $ofertasPorSede = $convocatoria->ofertas->groupBy('sede_id')->map(function ($ofertas) {
            return [
                'sede' => $ofertas->first()->sede,
                'cargos' => $ofertas->map(function ($oferta) {
                    return [
                        'id' => $oferta->id,
                        'cargo' => $oferta->cargo,
                        'vacantes' => $oferta->vacantes,
                    ];
                })
            ];
        })->values();

        // Mapear documentos requeridos
        $documentos = $convocatoria->documentosRequeridos->map(function ($doc) {
            return [
                'id' => $doc->id,
                'nombre' => $doc->nombre,
                'descripcion' => $doc->descripcion,
                'icono' => $doc->icono,
                'obligatorio' => $doc->pivot->obligatorio,
                'orden' => $doc->pivot->orden,
            ];
        });

        return response()->json([
            'convocatoria' => $convocatoria,
            'ofertas_por_sede' => $ofertasPorSede,
            'documentos_requeridos' => $documentos,
        ]);
    }

    /**
     * Verificar CI del postulante
     */
    public function checkCI(Request $request)
    {
        $request->validate(['ci' => 'required|string']);

        $postulante = Postulante::where('ci', $request->ci)->first();

        if (!$postulante) {
            return response()->json([
                'status' => 'nuevo',
                'message' => 'CI no registrado. Por favor complete sus datos.'
            ]);
        }

        // Cargar expediente completo
        $postulante->load([
            'formaciones',
            'experiencias',
            'capacitaciones',
            'producciones',
            'reconocimientos'
        ]);

        return response()->json([
            'status' => 'existente',
            'postulante' => $postulante,
            'tiene_expediente' => $postulante->formaciones->count() > 0
        ]);
    }

    /**
     * Consultar estado de postulaciones por CI
     */
    public function consultarEstado(Request $request)
    {
        $request->validate(['ci' => 'required|string']);

        $postulante = Postulante::where('ci', $request->ci)->first();

        if (!$postulante) {
            return response()->json([
                'encontrado' => false,
                'message' => 'No se encontraron postulaciones con este CI.'
            ]);
        }

        $postulaciones = Postulacion::where('postulante_id', $postulante->id)
            ->with(['oferta.convocatoria', 'oferta.sede', 'oferta.cargo'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'convocatoria' => $p->oferta->convocatoria->titulo,
                    'cargo' => $p->oferta->cargo->nombre,
                    'sede' => $p->oferta->sede->nombre,
                    'estado' => $p->estado,
                    'estado_texto' => $p->estado_texto,
                    'observaciones' => $p->observaciones,
                    'fecha' => $p->created_at->format('d/m/Y'),
                ];
            });

        return response()->json([
            'encontrado' => true,
            'postulante' => [
                'nombre' => $postulante->nombre_completo,
                'ci' => $postulante->ci,
            ],
            'postulaciones' => $postulaciones
        ]);
    }

    // ==================== RUTAS ADMINISTRATIVAS ====================

    /**
     * Listar todas las convocatorias
     */
    public function index()
    {
        $convocatorias = Convocatoria::withCount('ofertas')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($convocatorias);
    }

    /**
     * Crear nueva convocatoria
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_cierre' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'in:borrador,activa,cerrada',
            'ofertas' => 'nullable|array',
            'ofertas.*.sede_id' => 'required|exists:sedes,id',
            'ofertas.*.cargo_id' => 'required|exists:cargos,id',
            'ofertas.*.vacantes' => 'integer|min:1',
            'documentos' => 'nullable|array',
            'documentos.*.tipo_documento_id' => 'required|exists:tipos_documento,id',
            'documentos.*.obligatorio' => 'boolean',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $convData = collect($data)->except(['ofertas', 'documentos'])->toArray();
            $convData['slug'] = Str::slug($convData['titulo'] . '-' . Str::random(5));

            $convocatoria = Convocatoria::create($convData);

            // Crear ofertas
            if (!empty($data['ofertas'])) {
                foreach ($data['ofertas'] as $oferta) {
                    $exists = $convocatoria->ofertas()
                        ->where('sede_id', $oferta['sede_id'])
                        ->where('cargo_id', $oferta['cargo_id'])
                        ->exists();

                    if (!$exists) {
                        $convocatoria->ofertas()->create([
                            'sede_id' => $oferta['sede_id'],
                            'cargo_id' => $oferta['cargo_id'],
                            'vacantes' => $oferta['vacantes'] ?? 1
                        ]);
                    }
                }
            }

            // Crear documentos requeridos
            if (!empty($data['documentos'])) {
                foreach ($data['documentos'] as $index => $doc) {
                    $convocatoria->documentosRequeridos()->attach($doc['tipo_documento_id'], [
                        'obligatorio' => $doc['obligatorio'] ?? true,
                        'orden' => $index + 1
                    ]);
                }
            }

            return response()->json([
                'message' => 'Convocatoria creada exitosamente',
                'convocatoria' => $convocatoria->load(['ofertas', 'documentosRequeridos'])
            ], 201);
        });
    }

    /**
     * Ver detalle de convocatoria
     */
    public function show(Convocatoria $convocatoria)
    {
        $convocatoria->load(['ofertas.sede', 'ofertas.cargo', 'documentosRequeridos']);

        return response()->json($convocatoria);
    }

    /**
     * Actualizar convocatoria
     */
    public function update(Request $request, Convocatoria $convocatoria)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_cierre' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'in:borrador,activa,cerrada,finalizada',
            'documentos' => 'nullable|array',
            'documentos.*.tipo_documento_id' => 'required|exists:tipos_documento,id',
            'documentos.*.obligatorio' => 'boolean',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $convocatoria) {
            $convocatoria->update(collect($data)->except('documentos')->toArray());

            // Actualizar documentos requeridos si vienen en la request
            if (isset($data['documentos'])) {
                // Sincronizar documentos
                $syncData = [];
                foreach ($data['documentos'] as $index => $doc) {
                    $syncData[$doc['tipo_documento_id']] = [
                        'obligatorio' => $doc['obligatorio'] ?? true,
                        'orden' => $index + 1
                    ];
                }
                $convocatoria->documentosRequeridos()->sync($syncData);
            }

            return response()->json([
                'message' => 'Convocatoria actualizada',
                'convocatoria' => $convocatoria->load('documentosRequeridos')
            ]);
        });
    }

    /**
     * Eliminar convocatoria
     */
    public function destroy(Convocatoria $convocatoria)
    {
        // Verificar si tiene postulaciones
        $tienePostulaciones = Postulacion::whereIn(
            'oferta_id',
            $convocatoria->ofertas()->pluck('id')
        )->exists();

        if ($tienePostulaciones) {
            return response()->json([
                'message' => 'No se puede eliminar: tiene postulaciones registradas'
            ], 422);
        }

        $convocatoria->delete();

        return response()->json(['message' => 'Convocatoria eliminada']);
    }

    /**
     * Agregar oferta (cargo+sede) a convocatoria
     */
    public function agregarOferta(Request $request, Convocatoria $convocatoria)
    {
        $data = $request->validate([
            'sede_id' => 'required|exists:sedes,id',
            'cargo_id' => 'required|exists:cargos,id',
            'vacantes' => 'integer|min:1',
        ]);

        // Verificar que no exista la combinación
        $existe = Oferta::where([
            'convocatoria_id' => $convocatoria->id,
            'sede_id' => $data['sede_id'],
            'cargo_id' => $data['cargo_id'],
        ])->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Esta combinación sede-cargo ya existe en la convocatoria'
            ], 422);
        }

        $oferta = Oferta::create([
            'convocatoria_id' => $convocatoria->id,
            'sede_id' => $data['sede_id'],
            'cargo_id' => $data['cargo_id'],
            'vacantes' => $data['vacantes'] ?? 1,
        ]);

        $oferta->load(['sede', 'cargo']);

        return response()->json([
            'message' => 'Oferta agregada',
            'oferta' => $oferta
        ], 201);
    }

    /**
     * Eliminar oferta de convocatoria
     */
    public function eliminarOferta(Convocatoria $convocatoria, Oferta $oferta)
    {
        if ($oferta->convocatoria_id !== $convocatoria->id) {
            return response()->json(['message' => 'Oferta no pertenece a esta convocatoria'], 422);
        }

        if ($oferta->postulaciones()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: tiene postulaciones'
            ], 422);
        }

        $oferta->delete();

        return response()->json(['message' => 'Oferta eliminada']);
    }

    /**
     * Listar todas las postulaciones con filtros dinámicos
     */
    public function listarPostulaciones(Request $request)
    {
        $query = Postulacion::with(['postulante', 'oferta.convocatoria', 'oferta.sede', 'oferta.cargo']);

        // Filtro por Convocatoria (opcional)
        if ($request->has('convocatoria_id') && $request->convocatoria_id) {
            $query->whereHas('oferta', function ($q) use ($request) {
                $q->where('convocatoria_id', $request->convocatoria_id);
            });
        }

        // Filtro por Sede (opcional)
        if ($request->has('sede_id') && $request->sede_id) {
            $query->whereHas('oferta', function ($q) use ($request) {
                $q->where('sede_id', $request->sede_id);
            });
        }

        // Filtro por Cargo (opcional)
        if ($request->has('cargo_id') && $request->cargo_id) {
            $query->whereHas('oferta', function ($q) use ($request) {
                $q->where('cargo_id', $request->cargo_id);
            });
        }

        // Filtro por Estado (opcional)
        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }

        $postulaciones = $query->orderBy('created_at', 'desc')->get();

        return response()->json($postulaciones);
    }

    /**
     * Listar postulaciones de una convocatoria
     */
    public function postulaciones(Request $request, Convocatoria $convocatoria)
    {
        $query = Postulacion::whereIn('oferta_id', $convocatoria->ofertas()->pluck('id'))
            ->with(['postulante', 'oferta.sede', 'oferta.cargo']);

        // Filtros opcionales
        if ($request->has('sede_id')) {
            $query->whereHas('oferta', function ($q) use ($request) {
                $q->where('sede_id', $request->sede_id);
            });
        }

        if ($request->has('cargo_id')) {
            $query->whereHas('oferta', function ($q) use ($request) {
                $q->where('cargo_id', $request->cargo_id);
            });
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $postulaciones = $query->orderBy('created_at', 'desc')->get();

        return response()->json($postulaciones);
    }

    /**
     * Cambiar estado de una postulación
     */
    public function cambiarEstadoPostulacion(Request $request, Postulacion $postulacion)
    {
        $data = $request->validate([
            'estado' => 'required|in:pendiente,en_revision,observado,habilitado,rechazado,seleccionado',
            'observaciones' => 'nullable|string',
        ]);

        $postulacion->update($data);

        return response()->json([
            'message' => 'Estado actualizado',
            'postulacion' => $postulacion->load(['postulante', 'oferta.sede', 'oferta.cargo'])
        ]);
    }

    /**
     * Ver expediente completo de un postulante
     */
    public function verExpediente(Postulante $postulante)
    {
        $postulante->load([
            'formaciones',
            'experiencias',
            'capacitaciones',
            'producciones',
            'reconocimientos',
            'postulaciones.oferta.convocatoria'
        ]);

        return response()->json($postulante);
    }

    /**
     * Estadísticas de convocatoria
     */
    public function estadisticas(Convocatoria $convocatoria)
    {
        $ofertas = $convocatoria->ofertas()->with(['sede', 'cargo'])->get();

        $stats = [
            'total_ofertas' => $ofertas->count(),
            'total_postulaciones' => 0,
            'por_estado' => [],
            'por_sede' => [],
            'por_cargo' => [],
        ];

        foreach ($ofertas as $oferta) {
            $postulaciones = $oferta->postulaciones;
            $stats['total_postulaciones'] += $postulaciones->count();

            // Por sede
            $sedeNombre = $oferta->sede->nombre;
            if (!isset($stats['por_sede'][$sedeNombre])) {
                $stats['por_sede'][$sedeNombre] = 0;
            }
            $stats['por_sede'][$sedeNombre] += $postulaciones->count();

            // Por cargo
            $cargoNombre = $oferta->cargo->nombre;
            if (!isset($stats['por_cargo'][$cargoNombre])) {
                $stats['por_cargo'][$cargoNombre] = 0;
            }
            $stats['por_cargo'][$cargoNombre] += $postulaciones->count();

            // Por estado
            foreach ($postulaciones as $p) {
                if (!isset($stats['por_estado'][$p->estado])) {
                    $stats['por_estado'][$p->estado] = 0;
                }
                $stats['por_estado'][$p->estado]++;
            }
        }

        return response()->json($stats);
    }
}
