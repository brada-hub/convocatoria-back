<?php

namespace App\Services;

use App\Models\Postulante;
use App\Models\Formacion;
use App\Models\Experiencia;
use App\Models\Capacitacion;
use App\Models\Produccion;
use App\Models\Reconocimiento;
use Illuminate\Support\Facades\Storage;

class ExpedienteService
{
    /**
     * Guardar formaciones académicas
     */
    public function guardarFormaciones(Postulante $postulante, array $formaciones): void
    {
        foreach ($formaciones as $datos) {
            $archivo = null;
            if (isset($datos['archivo']) && $datos['archivo']) {
                $archivo = $datos['archivo']->store('expedientes/' . $postulante->ci . '/formaciones', 'public');
            }

            $postulante->formaciones()->updateOrCreate(
                [
                    'titulo_profesion' => $datos['titulo_profesion'],
                    'universidad' => $datos['universidad'],
                ],
                [
                    'nivel' => $datos['nivel'],
                    'anio_emision' => $datos['anio_emision'],
                    'archivo_pdf' => $archivo ?? $datos['archivo_pdf'] ?? null,
                ]
            );
        }
    }

    /**
     * Guardar experiencias profesionales
     */
    public function guardarExperiencias(Postulante $postulante, array $experiencias): void
    {
        $anioLimite = date('Y') - 5;

        foreach ($experiencias as $datos) {
            $anioFin = !empty($datos['anio_fin']) ? intval($datos['anio_fin']) : null;

            // Saltar experiencias muy antiguas
            if ($anioFin && $anioFin < $anioLimite) {
                continue;
            }

            $archivo = null;
            if (isset($datos['archivo']) && $datos['archivo']) {
                $archivo = $datos['archivo']->store('expedientes/' . $postulante->ci . '/experiencias', 'public');
            }

            $postulante->experiencias()->updateOrCreate(
                [
                    'cargo_desempenado' => $datos['cargo_desempenado'],
                    'empresa_institucion' => $datos['empresa_institucion'],
                    'anio_inicio' => $datos['anio_inicio'],
                ],
                [
                    'anio_fin' => $datos['anio_fin'] ?? null,
                    'funciones' => $datos['funciones'] ?? null,
                    'archivo_pdf' => $archivo ?? $datos['archivo_pdf'] ?? null,
                ]
            );
        }
    }

    /**
     * Guardar capacitaciones
     */
    public function guardarCapacitaciones(Postulante $postulante, array $capacitaciones): void
    {
        foreach ($capacitaciones as $datos) {
            if (empty($datos['nombre_curso'])) continue;

            $archivo = null;
            if (isset($datos['archivo']) && $datos['archivo']) {
                $archivo = $datos['archivo']->store('expedientes/' . $postulante->ci . '/capacitaciones', 'public');
            }

            $postulante->capacitaciones()->updateOrCreate(
                [
                    'nombre_curso' => $datos['nombre_curso'],
                    'institucion_emisora' => $datos['institucion_emisora'] ?? 'S/N',
                ],
                [
                    'carga_horaria' => $datos['carga_horaria'] ?? null,
                    'anio' => $datos['anio'],
                    'archivo_pdf' => $archivo ?? $datos['archivo_pdf'] ?? null,
                ]
            );
        }
    }

    /**
     * Guardar producciones intelectuales
     */
    public function guardarProducciones(Postulante $postulante, array $producciones): void
    {
        foreach ($producciones as $datos) {
            if (empty($datos['titulo'])) continue;

            $archivo = null;
            if (isset($datos['archivo']) && $datos['archivo']) {
                $archivo = $datos['archivo']->store('expedientes/' . $postulante->ci . '/producciones', 'public');
            }

            $postulante->producciones()->updateOrCreate(
                [
                    'titulo' => $datos['titulo'],
                    'tipo' => $datos['tipo'],
                ],
                [
                    'descripcion' => $datos['descripcion'] ?? null,
                    'anio' => $datos['anio'],
                    'archivo_pdf' => $archivo ?? $datos['archivo_pdf'] ?? null,
                ]
            );
        }
    }

    /**
     * Guardar reconocimientos
     */
    public function guardarReconocimientos(Postulante $postulante, array $reconocimientos): void
    {
        foreach ($reconocimientos as $datos) {
            if (empty($datos['titulo']) && empty($datos['tipo_reconocimiento'])) continue;

            $archivo = null;
            if (isset($datos['archivo']) && $datos['archivo']) {
                $archivo = $datos['archivo']->store('expedientes/' . $postulante->ci . '/reconocimientos', 'public');
            }

            $postulante->reconocimientos()->updateOrCreate(
                [
                    'titulo' => $datos['titulo'] ?? 'S/T',
                    'tipo_reconocimiento' => $datos['tipo_reconocimiento'] ?? 'S/R',
                ],
                [
                    'otorgado_por' => $datos['otorgado_por'] ?? null,
                    'anio' => $datos['anio'],
                    'archivo_pdf' => $archivo ?? $datos['archivo_pdf'] ?? null,
                ]
            );
        }
    }

    /**
     * Cargar expediente completo del postulante
     */
    public function cargarExpediente(Postulante $postulante): array
    {
        $postulante->load([
            'formaciones',
            'experiencias',
            'capacitaciones',
            'producciones',
            'reconocimientos',
            'documentos.tipoDocumento'
        ]);

        return [
            'formaciones' => $postulante->formaciones,
            'experiencias' => $postulante->experiencias,
            'capacitaciones' => $postulante->capacitaciones,
            'producciones' => $postulante->producciones,
            'reconocimientos' => $postulante->reconocimientos,
            'documentos' => $postulante->documentos,
        ];
    }
}
