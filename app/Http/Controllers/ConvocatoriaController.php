<?php

namespace App\Http\Controllers;

use App\Models\Convocatoria;
use App\Models\Oferta;
use App\Models\Postulante;
use App\Models\Postulacion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ConvocatoriaController extends Controller
{
    // ==================== RUTAS PÚBLICAS ====================

    /**
     * Listar convocatorias abiertas con sus ofertas y documentos requeridos
     */
    public function abiertas()
    {
        $convocatorias = Convocatoria::abiertas()
            ->with([
                'ofertas' => function ($query) {
                    $query->activos()->with(['sede', 'cargo']);
                },
                'documentosRequeridos' // Incluir documentos requeridos para el formulario de postulación
            ])
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
        $convocatorias = Convocatoria::with('documentosRequeridos')
            ->withCount('ofertas')
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
            'documentos.tipoDocumento',
            'postulaciones.oferta.convocatoria'
        ]);

        return response()->json($postulante);
    }

    /**
     * Descargar expediente completo como ZIP con todos los documentos
     */
    public function descargarExpedientePdf(Postulante $postulante)
    {
        $postulante->load([
            'formaciones',
            'experiencias',
            'capacitaciones',
            'producciones',
            'reconocimientos',
            'documentos.tipoDocumento',
            'postulaciones.oferta.convocatoria',
            'postulaciones.oferta.sede',
            'postulaciones.oferta.cargo'
        ]);

        // Generar HTML para el expediente
        $html = $this->generarHtmlExpediente($postulante);

        // Nombre base para archivos
        $nombreBase = "Expediente_{$postulante->nombres}_{$postulante->apellidos}_CI{$postulante->ci}";
        $nombreBase = preg_replace('/\s+/', '_', $nombreBase);

        // Recopilar todos los PDFs del postulante
        $archivos = $this->recopilarArchivosPdf($postulante);

        // Si hay archivos PDF y la clase ZipArchive existe, crear un ZIP
        if (count($archivos) > 0 && class_exists('ZipArchive')) {
            $zipPath = storage_path("app/temp/{$nombreBase}.zip");

            // Asegurar que existe el directorio temp
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                // Agregar el resumen HTML/PDF como primer documento
                $zip->addFromString("00_Resumen_Expediente.html", $html);

                // Agregar cada PDF encontrado
                $contador = 1;
                foreach ($archivos as $archivo) {
                    $rutaCompleta = storage_path('app/public/' . $archivo['ruta']);
                    if (file_exists($rutaCompleta)) {
                        $nombreArchivo = str_pad($contador, 2, '0', STR_PAD_LEFT) . '_' . $archivo['nombre'] . '.pdf';
                        $nombreArchivo = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $nombreArchivo);
                        $zip->addFile($rutaCompleta, $nombreArchivo);
                        $contador++;
                    }
                }

                $zip->close();

                // Devolver el ZIP
                return response()->download($zipPath, "{$nombreBase}.zip")->deleteFileAfterSend(true);
            }
        }

        // Si no hay PDFs o falla el ZIP, devolver solo el HTML
        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$nombreBase}.html\"");
    }

    /**
     * Recopilar todos los archivos PDF del postulante
     */
    private function recopilarArchivosPdf(Postulante $postulante): array
    {
        $archivos = [];

        // Formaciones
        foreach ($postulante->formaciones as $item) {
            if ($item->archivo_pdf) {
                $archivos[] = [
                    'ruta' => $item->archivo_pdf,
                    'nombre' => 'Formacion_' . Str::slug($item->titulo_profesion ?? 'documento')
                ];
            }
        }

        // Experiencias
        foreach ($postulante->experiencias as $item) {
            if ($item->archivo_pdf) {
                $archivos[] = [
                    'ruta' => $item->archivo_pdf,
                    'nombre' => 'Experiencia_' . Str::slug($item->cargo_desempenado ?? 'documento')
                ];
            }
        }

        // Capacitaciones
        foreach ($postulante->capacitaciones as $item) {
            if ($item->archivo_pdf) {
                $archivos[] = [
                    'ruta' => $item->archivo_pdf,
                    'nombre' => 'Capacitacion_' . Str::slug($item->nombre_curso ?? 'documento')
                ];
            }
        }

        // Producciones
        foreach ($postulante->producciones as $item) {
            if ($item->archivo_pdf) {
                $archivos[] = [
                    'ruta' => $item->archivo_pdf,
                    'nombre' => 'Produccion_' . Str::slug($item->titulo ?? 'documento')
                ];
            }
        }

        // Reconocimientos
        foreach ($postulante->reconocimientos as $item) {
            if ($item->archivo_pdf) {
                $archivos[] = [
                    'ruta' => $item->archivo_pdf,
                    'nombre' => 'Reconocimiento_' . Str::slug($item->titulo ?? 'documento')
                ];
            }
        }

        // Documentos generales
        foreach ($postulante->documentos as $item) {
            if ($item->archivo_pdf) {
                $archivos[] = [
                    'ruta' => $item->archivo_pdf,
                    'nombre' => 'Doc_' . Str::slug($item->tipoDocumento->nombre ?? 'documento')
                ];
            }
        }

        return $archivos;
    }

    /**
     * Generar HTML para el expediente PDF
     */
    private function generarHtmlExpediente(Postulante $postulante): string
    {
        $storageUrl = config('app.url') . '/storage/';

        $html = '<!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Expediente - ' . htmlspecialchars($postulante->nombres . ' ' . $postulante->apellidos) . '</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: Arial, sans-serif; font-size: 11px; line-height: 1.4; color: #333; padding: 20px; }
                .header { text-align: center; border-bottom: 2px solid #6B21A8; padding-bottom: 15px; margin-bottom: 20px; }
                .header h1 { color: #6B21A8; font-size: 18px; margin-bottom: 5px; }
                .header p { color: #666; font-size: 12px; }
                .info-personal { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
                .info-personal h2 { font-size: 14px; color: #6B21A8; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
                .info-grid { display: flex; flex-wrap: wrap; gap: 10px; }
                .info-item { flex: 1 1 45%; }
                .info-item label { font-weight: bold; color: #555; }
                .section { margin-bottom: 20px; page-break-inside: avoid; }
                .section h3 { font-size: 13px; color: #6B21A8; margin-bottom: 10px; padding: 8px; background: #f3e8ff; border-radius: 4px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
                th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
                th { background: #6B21A8; color: white; font-size: 10px; }
                tr:nth-child(even) { background: #f9f9f9; }
                .empty { text-align: center; color: #999; padding: 15px; font-style: italic; }
                .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
                .pdf-link { color: #6B21A8; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>EXPEDIENTE DIGITAL</h1>
                <p>Sistema de Convocatorias - UNITEPC</p>
            </div>

            <div class="info-personal">
                <h2>📋 Datos del Postulante</h2>
                <div class="info-grid">
                    <div class="info-item"><label>Nombre Completo:</label> ' . htmlspecialchars($postulante->nombres . ' ' . $postulante->apellidos) . '</div>
                    <div class="info-item"><label>CI:</label> ' . htmlspecialchars($postulante->ci) . '</div>
                    <div class="info-item"><label>Email:</label> ' . htmlspecialchars($postulante->email ?? 'No registrado') . '</div>
                    <div class="info-item"><label>Celular:</label> ' . htmlspecialchars($postulante->celular ?? 'No registrado') . '</div>
                </div>
            </div>';

        // Formación Académica
        $html .= '<div class="section">
            <h3>🎓 Formación Académica</h3>';
        if ($postulante->formaciones->count() > 0) {
            $html .= '<table>
                <thead><tr><th>Nivel</th><th>Título/Profesión</th><th>Universidad</th><th>Año</th><th>Documento</th></tr></thead>
                <tbody>';
            foreach ($postulante->formaciones as $f) {
                $pdfLink = $f->archivo_pdf ? '<a href="' . $storageUrl . $f->archivo_pdf . '" class="pdf-link">Ver PDF</a>' : 'Sin archivo';
                $html .= '<tr>
                    <td>' . htmlspecialchars($f->nivel) . '</td>
                    <td>' . htmlspecialchars($f->titulo_profesion) . '</td>
                    <td>' . htmlspecialchars($f->universidad) . '</td>
                    <td>' . htmlspecialchars($f->anio_emision) . '</td>
                    <td>' . $pdfLink . '</td>
                </tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<div class="empty">Sin registros de formación académica</div>';
        }
        $html .= '</div>';

        // Experiencia Laboral
        $html .= '<div class="section">
            <h3>💼 Experiencia Laboral</h3>';
        if ($postulante->experiencias->count() > 0) {
            $html .= '<table>
                <thead><tr><th>Cargo</th><th>Empresa/Institución</th><th>Inicio</th><th>Fin</th><th>Funciones</th><th>Doc</th></tr></thead>
                <tbody>';
            foreach ($postulante->experiencias as $e) {
                $pdfLink = $e->archivo_pdf ? '<a href="' . $storageUrl . $e->archivo_pdf . '" class="pdf-link">PDF</a>' : '-';
                $html .= '<tr>
                    <td>' . htmlspecialchars($e->cargo_desempenado) . '</td>
                    <td>' . htmlspecialchars($e->empresa_institucion) . '</td>
                    <td>' . htmlspecialchars($e->anio_inicio) . '</td>
                    <td>' . htmlspecialchars($e->anio_fin ?? 'Actual') . '</td>
                    <td>' . htmlspecialchars(Str::limit($e->funciones ?? '', 50)) . '</td>
                    <td>' . $pdfLink . '</td>
                </tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<div class="empty">Sin registros de experiencia laboral</div>';
        }
        $html .= '</div>';

        // Capacitaciones
        if ($postulante->capacitaciones->count() > 0) {
            $html .= '<div class="section">
                <h3>📚 Cursos y Capacitaciones</h3>
                <table>
                    <thead><tr><th>Curso</th><th>Institución</th><th>Horas</th><th>Año</th><th>Doc</th></tr></thead>
                    <tbody>';
            foreach ($postulante->capacitaciones as $c) {
                $pdfLink = $c->archivo_pdf ? '<a href="' . $storageUrl . $c->archivo_pdf . '" class="pdf-link">PDF</a>' : '-';
                $html .= '<tr>
                    <td>' . htmlspecialchars($c->nombre_curso) . '</td>
                    <td>' . htmlspecialchars($c->institucion_emisora ?? '-') . '</td>
                    <td>' . htmlspecialchars($c->carga_horaria ?? '-') . '</td>
                    <td>' . htmlspecialchars($c->anio) . '</td>
                    <td>' . $pdfLink . '</td>
                </tr>';
            }
            $html .= '</tbody></table></div>';
        }

        // Producciones
        if ($postulante->producciones->count() > 0) {
            $html .= '<div class="section">
                <h3>📖 Producción Intelectual</h3>
                <table>
                    <thead><tr><th>Título</th><th>Tipo</th><th>Año</th><th>Doc</th></tr></thead>
                    <tbody>';
            foreach ($postulante->producciones as $p) {
                $pdfLink = $p->archivo_pdf ? '<a href="' . $storageUrl . $p->archivo_pdf . '" class="pdf-link">PDF</a>' : '-';
                $html .= '<tr>
                    <td>' . htmlspecialchars($p->titulo) . '</td>
                    <td>' . htmlspecialchars($p->tipo) . '</td>
                    <td>' . htmlspecialchars($p->anio) . '</td>
                    <td>' . $pdfLink . '</td>
                </tr>';
            }
            $html .= '</tbody></table></div>';
        }

        // Reconocimientos
        if ($postulante->reconocimientos->count() > 0) {
            $html .= '<div class="section">
                <h3>🏆 Reconocimientos</h3>
                <table>
                    <thead><tr><th>Título</th><th>Otorgado por</th><th>Año</th><th>Doc</th></tr></thead>
                    <tbody>';
            foreach ($postulante->reconocimientos as $r) {
                $pdfLink = $r->archivo_pdf ? '<a href="' . $storageUrl . $r->archivo_pdf . '" class="pdf-link">PDF</a>' : '-';
                $html .= '<tr>
                    <td>' . htmlspecialchars($r->titulo ?? $r->tipo_reconocimiento) . '</td>
                    <td>' . htmlspecialchars($r->otorgado_por ?? '-') . '</td>
                    <td>' . htmlspecialchars($r->anio) . '</td>
                    <td>' . $pdfLink . '</td>
                </tr>';
            }
            $html .= '</tbody></table></div>';
        }

        // Documentos Generales
        if ($postulante->documentos->count() > 0) {
            $html .= '<div class="section">
                <h3>📁 Documentos Generales</h3>
                <table>
                    <thead><tr><th>Tipo de Documento</th><th>Enlace</th></tr></thead>
                    <tbody>';
            foreach ($postulante->documentos as $d) {
                $pdfLink = $d->archivo_pdf ? '<a href="' . $storageUrl . $d->archivo_pdf . '" class="pdf-link">Ver PDF</a>' : '-';
                $html .= '<tr>
                    <td>' . htmlspecialchars($d->tipoDocumento->nombre ?? 'Documento') . '</td>
                    <td>' . $pdfLink . '</td>
                </tr>';
            }
            $html .= '</tbody></table></div>';
        }

        // Postulaciones
        if ($postulante->postulaciones->count() > 0) {
            $html .= '<div class="section">
                <h3>📝 Historial de Postulaciones</h3>
                <table>
                    <thead><tr><th>Convocatoria</th><th>Cargo</th><th>Sede</th><th>Estado</th><th>Fecha</th></tr></thead>
                    <tbody>';
            foreach ($postulante->postulaciones as $p) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($p->oferta->convocatoria->titulo ?? '-') . '</td>
                    <td>' . htmlspecialchars($p->oferta->cargo->nombre ?? '-') . '</td>
                    <td>' . htmlspecialchars($p->oferta->sede->nombre ?? '-') . '</td>
                    <td>' . htmlspecialchars(ucfirst($p->estado)) . '</td>
                    <td>' . $p->created_at->format('d/m/Y') . '</td>
                </tr>';
            }
            $html .= '</tbody></table></div>';
        }

        $html .= '
            <div class="footer">
                <p>Documento generado el ' . now()->format('d/m/Y H:i') . '</p>
                <p>Sistema de Convocatorias UNITEPC - Todos los derechos reservados</p>
            </div>
        </body>
        </html>';

        return $html;
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

    /**
     * Proxy para descargar documentos y evitar CORS
     */
    public function descargarDocumentoProxy(Request $request)
    {
        $path = $request->query('path');

        if (!$path) {
            return response()->json(['message' => 'Path requerido'], 400);
        }

        \Illuminate\Support\Facades\Log::info("Busqueda Proxy: " . $path);

        // 1. Intentar con Storage Facade usando FORWARD SLASH (Flysystem nativo)
        $storagePath = str_replace('\\', '/', $path);
        if (Storage::disk('public')->exists($storagePath)) {
            return response()->file(Storage::disk('public')->path($storagePath));
        }

        // 2. Intentar acceso directo al sistema de archivos usando DIRECTORY_SEPARATOR
        $osPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        // Construir la ruta asumiendo que está en storage/app/public
        $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $osPath);

        if (file_exists($fullPath)) {
            return response()->file($fullPath);
        }

        // 3. Fallback: Intentar limpiar 'storage/' si viene en el path y probar ambas estrategias
        $cleanPath = str_replace('storage/', '', str_replace('\\', '/', $path)); // Limpieza genérica

        if (Storage::disk('public')->exists($cleanPath)) {
            return response()->file(Storage::disk('public')->path($cleanPath));
        }

        // 4. Última opción: ver si es una ruta absoluta válida
        // (Solo por seguridad si el path ya venía completo)
        if (file_exists($osPath)) {
             return response()->file($osPath);
        }

        \Illuminate\Support\Facades\Log::error("Proxy 404 - No encontrado. \n Original: $path \n StoragePath: $storagePath \n FullPath: $fullPath");

        return response()->json(['message' => 'Archivo no encontrado'], 404);
    }
}
