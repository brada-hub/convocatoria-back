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
use App\Models\Convocatoria;
use App\Services\PostulacionService;
use App\Services\ExpedienteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PostulacionController extends Controller
{
    protected PostulacionService $postulacionService;
    protected ExpedienteService $expedienteService;

    public function __construct(PostulacionService $postulacionService, ExpedienteService $expedienteService)
    {
        $this->postulacionService = $postulacionService;
        $this->expedienteService = $expedienteService;
    }

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
            'foto_perfil' => 'nullable|image|max:2048',
        ]);

        $postulante = $this->postulacionService->registrarPostulante(
            $data,
            $request->file('foto_perfil')
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
            // Procesar cada sección usando el servicio
            if ($request->has('formaciones')) {
                $formaciones = $this->prepararDatosConArchivos($request, 'formaciones');
                $this->expedienteService->guardarFormaciones($postulante, $formaciones);
            }

            if ($request->has('experiencias')) {
                $experiencias = $this->prepararDatosConArchivos($request, 'experiencias');
                $this->expedienteService->guardarExperiencias($postulante, $experiencias);
            }

            if ($request->has('capacitaciones')) {
                $capacitaciones = $this->prepararDatosConArchivos($request, 'capacitaciones');
                $this->expedienteService->guardarCapacitaciones($postulante, $capacitaciones);
            }

            if ($request->has('producciones')) {
                $producciones = $this->prepararDatosConArchivos($request, 'producciones');
                $this->expedienteService->guardarProducciones($postulante, $producciones);
            }

            if ($request->has('reconocimientos')) {
                $reconocimientos = $this->prepararDatosConArchivos($request, 'reconocimientos');
                $this->expedienteService->guardarReconocimientos($postulante, $reconocimientos);
            }

            $postulante->load([
                'formaciones',
                'experiencias',
                'capacitaciones',
                'producciones',
                'reconocimientos'
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

            if (!$oferta->convocatoria->esta_abierta) {
                $errores[] = "La convocatoria para {$oferta->cargo->nombre} en {$oferta->sede->nombre} no está abierta";
                continue;
            }

            $existe = Postulacion::where([
                'postulante_id' => $postulante->id,
                'oferta_id' => $ofertaId,
            ])->exists();

            if ($existe) {
                $errores[] = "Ya tiene una postulación para {$oferta->cargo->nombre} en {$oferta->sede->nombre}";
                continue;
            }

            Postulacion::create([
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
                'formaciones.*.nivel' => 'required|exists:niveles_academicos,slug',
                'formaciones.*.titulo_profesion' => 'required|string|max:255',
                'formaciones.*.universidad' => 'required|string|max:255',
                'formaciones.*.anio_emision' => 'required|integer|between:1900,2100',
                'experiencias.*.cargo_desempenado' => 'required|string|max:255',
                'experiencias.*.empresa_institucion' => 'required|string|max:255',
                'experiencias.*.anio_inicio' => 'required|integer|between:1900,2100',
                'experiencias.*.anio_fin' => 'nullable|integer|between:1900,2100',
                'capacitaciones.*.anio' => 'nullable|integer|between:1900,2100',
                'producciones.*.anio' => 'nullable|integer|between:1900,2100',
                'reconocimientos.*.anio' => 'nullable|integer|between:1900,2100',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation Failed:', $e->errors());
            throw $e;
        }

        Log::info('Proceso Completo Request Data:', $request->all());

        return DB::transaction(function () use ($request) {
            // 1. Crear/actualizar postulante
            $postulante = $this->postulacionService->registrarPostulante(
                $request->only(['ci', 'nombres', 'apellidos', 'email', 'celular']),
                $request->file('foto_perfil')
            );

            // 2. Guardar expediente
            if ($request->has('formaciones')) {
                $formaciones = $this->prepararDatosConArchivos($request, 'formaciones');
                $this->expedienteService->guardarFormaciones($postulante, $formaciones);
            }

            if ($request->has('experiencias')) {
                $experiencias = $this->prepararDatosConArchivos($request, 'experiencias');
                $this->expedienteService->guardarExperiencias($postulante, $experiencias);
            }

            if ($request->has('capacitaciones')) {
                $capacitaciones = $this->prepararDatosConArchivos($request, 'capacitaciones');
                $this->expedienteService->guardarCapacitaciones($postulante, $capacitaciones);
            }

            if ($request->has('producciones')) {
                $producciones = $this->prepararDatosConArchivos($request, 'producciones');
                $this->expedienteService->guardarProducciones($postulante, $producciones);
            }

            if ($request->has('reconocimientos')) {
                $reconocimientos = $this->prepararDatosConArchivos($request, 'reconocimientos');
                $this->expedienteService->guardarReconocimientos($postulante, $reconocimientos);
            }

            // 3. Guardar documentos requeridos
            if ($request->has('documentos')) {
                $documentos = $this->prepararDocumentos($request);
                $this->postulacionService->guardarDocumentos($postulante, $documentos);
            }

            // 4. Crear postulaciones
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

                Postulacion::firstOrCreate(
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
                Log::warning('No se pudo crear ninguna postulación:', ['errores' => $errores, 'ci' => $request->ci]);
                return response()->json([
                    'message' => 'No se pudo procesar la postulación. Verifique que la convocatoria esté abierta.',
                    'errores' => $errores
                ], 422);
            }

            return response()->json([
                'message' => 'Postulación completada exitosamente',
                'postulante' => $postulante->fresh([
                    'formaciones',
                    'experiencias',
                    'capacitaciones',
                    'producciones',
                    'reconocimientos'
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

        if ($registro->archivo_pdf && Storage::disk('public')->exists($registro->archivo_pdf)) {
            Storage::disk('public')->delete($registro->archivo_pdf);
        }

        $registro->delete();

        return response()->json(['message' => 'Registro eliminado']);
    }

    /**
     * Helper: Preparar datos con archivos del request
     */
    private function prepararDatosConArchivos(Request $request, string $seccion): array
    {
        $datos = $request->input($seccion, []);

        foreach ($datos as $index => &$item) {
            if ($request->hasFile("{$seccion}.{$index}.archivo")) {
                $item['archivo'] = $request->file("{$seccion}.{$index}.archivo");
            }
        }

        return $datos;
    }

    /**
     * Helper: Preparar documentos requeridos del request
     */
    private function prepararDocumentos(Request $request): array
    {
        $documentos = $request->input('documentos', []);

        foreach ($documentos as $index => &$doc) {
            if ($request->hasFile("documentos.{$index}.archivo")) {
                $doc['archivo'] = $request->file("documentos.{$index}.archivo");
            }
        }

        return $documentos;
    }
}
