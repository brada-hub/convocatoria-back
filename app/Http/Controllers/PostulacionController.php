<?php

namespace App\Http\Controllers;

use App\Models\Postulante;
use App\Models\Postulacion;
use App\Models\Formacion;
use App\Models\Experiencia;
use App\Models\Capacitacion;
use App\Models\Reconocimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostulacionController extends Controller
{
    public function store(Request $request)
    {
        // Esta es una versión simplificada pero robusta de la lógica de guardado
        return DB::transaction(function () use ($request) {
            // 1. Crear o actualizar postulante
            $postulante = Postulante::updateOrCreate(
                ['ci' => $request->ci],
                [
                    'nombre_completo' => $request->nombre_completo,
                    'email' => $request->email,
                    'telefono' => $request->telefono
                ]
            );

            // 2. Crear vínculo con convocatoria
            Postulacion::firstOrCreate([
                'postulante_id' => $postulante->id,
                'convocatoria_id' => $request->convocatoria_id
            ]);

            // 3. Guardar Formaciones (Loop si vienen múltiples)
            if ($request->has('formaciones')) {
                foreach ($request->formaciones as $f) {
                    $path = isset($f['archivo']) ? $f['archivo']->store('respaldos/formacion', 'public') : null;
                    $postulante->formaciones()->create([
                        'tipo' => $f['tipo'],
                        'titulo' => $f['titulo'],
                        'universidad' => $f['universidad'],
                        'anio_emision' => $f['anio_emision'],
                        'archivo_respaldo' => $path
                    ]);
                }
            }

            // 4. Guardar Experiencias con Validación de 5 años
            if ($request->has('experiencias')) {
                foreach ($request->experiencias as $e) {
                    // Validación en el Backend también por seguridad
                    $fechaFin = new \DateTime($e['fecha_fin']);
                    $hoy = new \DateTime();
                    $diff = $hoy->diff($fechaFin)->y;

                    if ($diff > 5) {
                        continue; // O lanzar error según prefieras
                    }

                    $path = isset($e['archivo']) ? $e['archivo']->store('respaldos/experiencia', 'public') : null;
                    $postulante->experiencias()->create([
                        'tipo' => $e['tipo'],
                        'cargo' => $e['cargo'],
                        'institucion' => $e['institucion'],
                        'fecha_inicio' => $e['fecha_inicio'],
                        'fecha_fin' => $e['fecha_fin'],
                        'archivo_respaldo' => $path
                    ]);
                }
            }

            return response()->json(['message' => 'Postulación guardada con éxito']);
        });
    }
}
