<?php

namespace App\Http\Controllers;

use App\Models\Postulante;
use App\Models\Postulacion;
use App\Models\Formacion;
use App\Models\Experiencia;
use App\Models\Capacitacion;
use App\Models\Produccion;
use App\Models\Reconocimiento;
use App\Models\Oferta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PostulacionController extends Controller
{
    /**
     * Registrar o actualizar postulante con sus datos básicos
     */
    public function registrarPostulante(Request $request)
    {
        $data = $request->validate([
            'ci' => 'required|string|max:20',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'celular' => 'required|string|max:20',
            'foto_perfil' => 'nullable|image|max:2048', // Max 2MB
        ]);

        // Manejar foto
        if ($request->hasFile('foto_perfil')) {
            $data['foto_perfil'] = $request->file('foto_perfil')
                ->store('postulantes/fotos', 'public');
        }

        $postulante = Postulante::updateOrCreate(
            ['ci' => $data['ci']],
            $data
        );

        return response()->json([
            'message' => 'Datos guardados exitosamente',
            'postulante' => $postulante
        ]);
    }

    /**
     * Guardar expediente completo (formaciones, experiencias, etc.)
     */
    public function guardarExpediente(Request $request)
    {
        $request->validate([
            'ci' => 'required|exists:postulantes,ci',
            'formaciones.*.anio_emision' => 'nullable|integer|between:1900,2100',
            'experiencias.*.anio_inicio' => 'nullable|integer|between:1900,2100',
            'experiencias.*.anio_fin' => 'nullable|integer|between:1900,2100',
            'capacitaciones.*.anio' => 'nullable|integer|between:1900,2100',
            'producciones.*.anio' => 'nullable|integer|between:1900,2100',
            'reconocimientos.*.anio' => 'nullable|integer|between:1900,2100',
        ]);

        $postulante = Postulante::where('ci', $request->ci)->firstOrFail();

        return DB::transaction(function () use ($request, $postulante) {
            // Procesar Formaciones
            if ($request->has('formaciones')) {
                foreach ($request->formaciones as $index => $item) {
                    $archivo = null;
                    if ($request->hasFile("formaciones.{$index}.archivo")) {
                        $archivo = $request->file("formaciones.{$index}.archivo")
                            ->store('expedientes/formaciones', 'public');
                    }

                    $postulante->formaciones()->create([
                        'nivel' => $item['nivel'],
                        'titulo_profesion' => $item['titulo_profesion'],
                        'universidad' => $item['universidad'],
                        'anio_emision' => $item['anio_emision'],
                        'archivo_pdf' => $archivo,
                    ]);
                }
            }

            // Procesar Experiencias (con validación de 5 años)
            if ($request->has('experiencias')) {
                $anioLimite = date('Y') - 5;

                foreach ($request->experiencias as $index => $item) {
                    $anioFin = !empty($item['anio_fin']) ? intval($item['anio_fin']) : null;

                    // Validar que sea de los últimos 5 años (si no es actual)
                    if ($anioFin && $anioFin < $anioLimite) {
                        continue; // Saltar experiencias muy antiguas
                    }

                    $archivo = null;
                    if ($request->hasFile("experiencias.{$index}.archivo")) {
                        $archivo = $request->file("experiencias.{$index}.archivo")
                            ->store('expedientes/experiencias', 'public');
                    }

                    $postulante->experiencias()->create([
                        'cargo_desempenado' => $item['cargo_desempenado'],
                        'empresa_institucion' => $item['empresa_institucion'],
                        'anio_inicio' => $item['anio_inicio'],
                        'anio_fin' => $item['anio_fin'] ?? null,
                        'funciones' => $item['funciones'] ?? null,
                        'archivo_pdf' => $archivo,
                    ]);
                }
            }

            // Procesar Capacitaciones
            if ($request->has('capacitaciones')) {
                foreach ($request->capacitaciones as $index => $item) {
                    $archivo = null;
                    if ($request->hasFile("capacitaciones.{$index}.archivo")) {
                        $archivo = $request->file("capacitaciones.{$index}.archivo")
                            ->store('expedientes/capacitaciones', 'public');
                    }

                    $postulante->capacitaciones()->create([
                        'nombre_curso' => $item['nombre_curso'],
                        'institucion_emisora' => $item['institucion_emisora'],
                        'carga_horaria' => $item['carga_horaria'] ?? null,
                        'anio' => $item['anio'],
                        'archivo_pdf' => $archivo,
                    ]);
                }
            }

            // Procesar Producciones
            if ($request->has('producciones')) {
                foreach ($request->producciones as $index => $item) {
                    $archivo = null;
                    if ($request->hasFile("producciones.{$index}.archivo")) {
                        $archivo = $request->file("producciones.{$index}.archivo")
                            ->store('expedientes/producciones', 'public');
                    }

                    $postulante->producciones()->create([
                        'tipo' => $item['tipo'],
                        'titulo' => $item['titulo'],
                        'descripcion' => $item['descripcion'] ?? null,
                        'anio' => $item['anio'],
                        'archivo_pdf' => $archivo,
                    ]);
                }
            }

            // Procesar Reconocimientos
            if ($request->has('reconocimientos')) {
                foreach ($request->reconocimientos as $index => $item) {
                    $archivo = null;
                    if ($request->hasFile("reconocimientos.{$index}.archivo")) {
                        $archivo = $request->file("reconocimientos.{$index}.archivo")
                            ->store('expedientes/reconocimientos', 'public');
                    }

                    $postulante->reconocimientos()->create([
                        'tipo_reconocimiento' => $item['tipo_reconocimiento'],
                        'titulo' => $item['titulo'],
                        'otorgado_por' => $item['otorgado_por'] ?? null,
                        'anio' => $item['anio'],
                        'archivo_pdf' => $archivo,
                    ]);
                }
            }

            // Recargar relaciones
            $postulante->load([
                'formaciones', 'experiencias', 'capacitaciones',
                'producciones', 'reconocimientos'
            ]);

            return response()->json([
                'message' => 'Expediente guardado exitosamente',
                'postulante' => $postulante
            ]);
        });
    }

    /**
     * Enviar postulaciones a múltiples cargos
     */
    public function enviarPostulaciones(Request $request)
    {
        $request->validate([
            'ci' => 'required|exists:postulantes,ci',
            'ofertas' => 'required|array|min:1',
            'ofertas.*' => 'exists:convocatoria_sede_cargo,id',
        ]);

        $postulante = Postulante::where('ci', $request->ci)->firstOrFail();

        // Verificar que tenga expediente
        if ($postulante->formaciones()->count() === 0) {
            return response()->json([
                'message' => 'Debe completar su expediente antes de postular'
            ], 422);
        }

        $resultados = [];
        $errores = [];

        foreach ($request->ofertas as $ofertaId) {
            $oferta = Oferta::with(['convocatoria', 'sede', 'cargo'])->find($ofertaId);

            // Verificar que la convocatoria esté abierta
            if (!$oferta->convocatoria->esta_abierta) {
                $errores[] = "La convocatoria para {$oferta->cargo->nombre} en {$oferta->sede->nombre} no está abierta";
                continue;
            }

            // Verificar si ya postuló
            $existe = Postulacion::where([
                'postulante_id' => $postulante->id,
                'oferta_id' => $ofertaId,
            ])->exists();

            if ($existe) {
                $errores[] = "Ya tiene una postulación para {$oferta->cargo->nombre} en {$oferta->sede->nombre}";
                continue;
            }

            // Crear postulación
            $postulacion = Postulacion::create([
                'postulante_id' => $postulante->id,
                'oferta_id' => $ofertaId,
                'estado' => 'pendiente',
            ]);

            $resultados[] = [
                'cargo' => $oferta->cargo->nombre,
                'sede' => $oferta->sede->nombre,
                'estado' => 'Pendiente',
            ];
        }

        return response()->json([
            'message' => count($resultados) > 0
                ? 'Postulaciones enviadas exitosamente'
                : 'No se pudieron enviar las postulaciones',
            'postulaciones' => $resultados,
            'errores' => $errores,
        ], count($resultados) > 0 ? 200 : 422);
    }

    /**
     * Proceso completo: datos + expediente + postulaciones
     */
    public function procesoCompleto(Request $request)
    {
        try {
            $request->validate([
                'ci' => 'required|string|max:20',
                'nombres' => 'required|string|max:100',
                'apellidos' => 'required|string|max:100',
                'celular' => 'required|string|max:20',
                'email' => 'nullable|email|max:100',
                'ofertas' => 'required|array|min:1',
                'ofertas.*' => 'exists:convocatoria_sede_cargo,id',

                // Formaciones
                'formaciones.*.nivel' => 'required|in:licenciatura,maestria,doctorado,diplomado,especialidad',
                'formaciones.*.titulo_profesion' => 'required|string|max:255',
                'formaciones.*.universidad' => 'required|string|max:255',
                'formaciones.*.anio_emision' => 'required|integer|between:1900,2100',

                // Experiencias
                'experiencias.*.cargo_desempenado' => 'required|string|max:255',
                'experiencias.*.empresa_institucion' => 'required|string|max:255',
                'experiencias.*.anio_inicio' => 'required|integer|between:1900,2100',
                'experiencias.*.anio_fin' => 'nullable|integer|between:1900,2100',

                // Otros
                'capacitaciones.*.anio' => 'nullable|integer|between:1900,2100',
                'producciones.*.anio' => 'nullable|integer|between:1900,2100',
                'reconocimientos.*.anio' => 'nullable|integer|between:1900,2100',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Failed:', $e->errors());
            throw $e;
        }

        \Log::info('Proceso Completo Request Data:', $request->all());

        return DB::transaction(function () use ($request) {
            // 1. Crear/actualizar postulante
            $postulante = Postulante::updateOrCreate(
                ['ci' => $request->ci],
                [
                    'nombres' => $request->nombres,
                    'apellidos' => $request->apellidos,
                    'email' => $request->email ?? null,
                    'celular' => $request->celular,
                ]
            );

            // 2. Manejar foto si viene
            if ($request->hasFile('foto_perfil')) {
                $postulante->foto_perfil = $request->file('foto_perfil')
                    ->store('postulantes/fotos', 'public');
                $postulante->save();
            }

            // 3. Guardar formaciones
            if ($request->has('formaciones')) {
                foreach ($request->formaciones as $index => $item) {
                    $archivo = null;
                    if ($request->hasFile("formaciones.{$index}.archivo")) {
                        $archivo = $request->file("formaciones.{$index}.archivo")
                            ->store('expedientes/formaciones', 'public');
                    }

                    $postulante->formaciones()->updateOrCreate(
                        [
                            'titulo_profesion' => $item['titulo_profesion'],
                            'universidad' => $item['universidad'],
                        ],
                        [
                            'nivel' => $item['nivel'],
                            'anio_emision' => $item['anio_emision'],
                            'archivo_pdf' => $archivo ?? $item['archivo_pdf'] ?? null,
                        ]
                    );
                }
            }

            // 4. Guardar experiencias
            if ($request->has('experiencias')) {
                $anioLimite = date('Y') - 5;

                foreach ($request->experiencias as $index => $item) {
                    $anioFin = !empty($item['anio_fin']) ? intval($item['anio_fin']) : null;

                    if ($anioFin && $anioFin < $anioLimite) {
                        continue;
                    }

                    $archivo = null;
                    if ($request->hasFile("experiencias.{$index}.archivo")) {
                        $archivo = $request->file("experiencias.{$index}.archivo")
                            ->store('expedientes/experiencias', 'public');
                    }

                    $postulante->experiencias()->updateOrCreate(
                        [
                            'cargo_desempenado' => $item['cargo_desempenado'],
                            'empresa_institucion' => $item['empresa_institucion'],
                            'anio_inicio' => $item['anio_inicio'],
                        ],
                        [
                            'anio_fin' => $item['anio_fin'] ?? null,
                            'funciones' => $item['funciones'] ?? null,
                            'archivo_pdf' => $archivo ?? $item['archivo_pdf'] ?? null,
                        ]
                    );
                }
            }

            // 5. Guardar capacitaciones
            if ($request->has('capacitaciones')) {
                foreach ($request->capacitaciones as $index => $item) {
                    // Saltar si no tiene nombre del curso
                    if (empty($item['nombre_curso'])) continue;

                    $archivo = null;
                    if ($request->hasFile("capacitaciones.{$index}.archivo")) {
                        $archivo = $request->file("capacitaciones.{$index}.archivo")
                            ->store('expedientes/capacitaciones', 'public');
                    }

                    $postulante->capacitaciones()->updateOrCreate(
                        [
                            'nombre_curso' => $item['nombre_curso'],
                            'institucion_emisora' => $item['institucion_emisora'] ?? 'S/N',
                        ],
                        [
                            'carga_horaria' => $item['carga_horaria'] ?? null,
                            'anio' => $item['anio'],
                            'archivo_pdf' => $archivo ?? $item['archivo_pdf'] ?? null,
                        ]
                    );
                }
            }

            // 6. Guardar producciones
            if ($request->has('producciones')) {
                foreach ($request->producciones as $index => $item) {
                    // Saltar si no tiene título
                    if (empty($item['titulo'])) continue;

                    $archivo = null;
                    if ($request->hasFile("producciones.{$index}.archivo")) {
                        $archivo = $request->file("producciones.{$index}.archivo")
                            ->store('expedientes/producciones', 'public');
                    }

                    $postulante->producciones()->updateOrCreate(
                        [
                            'titulo' => $item['titulo'],
                            'tipo' => $item['tipo'],
                        ],
                        [
                            'descripcion' => $item['descripcion'] ?? null,
                            'anio' => $item['anio'],
                            'archivo_pdf' => $archivo ?? $item['archivo_pdf'] ?? null,
                        ]
                    );
                }
            }

            // 7. Guardar reconocimientos
            if ($request->has('reconocimientos')) {
                foreach ($request->reconocimientos as $index => $item) {
                    // Saltar si no tiene título ni tipo
                    if (empty($item['titulo']) && empty($item['tipo_reconocimiento'])) continue;

                    $archivo = null;
                    if ($request->hasFile("reconocimientos.{$index}.archivo")) {
                        $archivo = $request->file("reconocimientos.{$index}.archivo")
                            ->store('expedientes/reconocimientos', 'public');
                    }

                    $postulante->reconocimientos()->updateOrCreate(
                        [
                            'titulo' => $item['titulo'] ?? 'S/T',
                            'tipo_reconocimiento' => $item['tipo_reconocimiento'] ?? 'S/R',
                        ],
                        [
                            'otorgado_por' => $item['otorgado_por'] ?? null,
                            'anio' => $item['anio'],
                            'archivo_pdf' => $archivo ?? $item['archivo_pdf'] ?? null,
                        ]
                    );
                }
            }

            // 8. Crear postulaciones
            $postulacionesCreadas = [];
            $errores = [];

            foreach ($request->ofertas as $ofertaId) {
                $oferta = Oferta::with(['convocatoria', 'sede', 'cargo'])->find($ofertaId);

                if (!$oferta) {
                    $errores[] = "Oferta no encontrada: {$ofertaId}";
                    continue;
                }

                if (!$oferta->convocatoria->esta_abierta) {
                    $errores[] = "Convocatoria cerrada para {$oferta->cargo->nombre}";
                    continue;
                }

                $postulacion = Postulacion::firstOrCreate(
                    [
                        'postulante_id' => $postulante->id,
                        'oferta_id' => $ofertaId,
                    ],
                    ['estado' => 'pendiente']
                );

                $postulacionesCreadas[] = [
                    'cargo' => $oferta->cargo->nombre,
                    'sede' => $oferta->sede->nombre,
                ];
            }

            if (count($postulacionesCreadas) === 0) {
                \Log::warning('No se pudo crear ninguna postulación:', ['errores' => $errores, 'ci' => $request->ci]);
                return response()->json([
                    'message' => 'No se pudo procesar la postulación. Verifique que la convocatoria esté abierta.',
                    'errores' => $errores
                ], 422);
            }

            return response()->json([
                'message' => 'Postulación completada exitosamente',
                'postulante' => $postulante->fresh([
                    'formaciones', 'experiencias', 'capacitaciones',
                    'producciones', 'reconocimientos'
                ]),
                'postulaciones' => $postulacionesCreadas,
                'errores' => $errores,
            ]);
        });
    }

    /**
     * Eliminar un registro del expediente
     */
    public function eliminarRegistro(Request $request)
    {
        $request->validate([
            'ci' => 'required|exists:postulantes,ci',
            'tipo' => 'required|in:formacion,experiencia,capacitacion,produccion,reconocimiento',
            'id' => 'required|integer',
        ]);

        $postulante = Postulante::where('ci', $request->ci)->firstOrFail();

        $modelos = [
            'formacion' => Formacion::class,
            'experiencia' => Experiencia::class,
            'capacitacion' => Capacitacion::class,
            'produccion' => Produccion::class,
            'reconocimiento' => Reconocimiento::class,
        ];

        $modelo = $modelos[$request->tipo];
        $registro = $modelo::where('id', $request->id)
            ->where('postulante_id', $postulante->id)
            ->first();

        if (!$registro) {
            return response()->json(['message' => 'Registro no encontrado'], 404);
        }

        // Eliminar archivo si existe
        if ($registro->archivo_pdf && Storage::disk('public')->exists($registro->archivo_pdf)) {
            Storage::disk('public')->delete($registro->archivo_pdf);
        }

        $registro->delete();

        return response()->json(['message' => 'Registro eliminado']);
    }
}
