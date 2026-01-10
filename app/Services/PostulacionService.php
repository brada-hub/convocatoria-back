<?php

namespace App\Services;

use App\Models\Postulante;
use App\Models\Postulacion;
use App\Models\DocumentoPostulante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostulacionService
{
    protected ExpedienteService $expedienteService;

    public function __construct(ExpedienteService $expedienteService)
    {
        $this->expedienteService = $expedienteService;
    }

    /**
     * Registrar o actualizar un postulante
     */
    public function registrarPostulante(array $datos, $fotoPerfil = null): Postulante
    {
        $postulante = Postulante::updateOrCreate(
            ['ci' => $datos['ci']],
            [
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'email' => $datos['email'] ?? null,
                'celular' => $datos['celular'],
            ]
        );

        // Procesar foto de perfil si existe
        if ($fotoPerfil) {
            $path = $fotoPerfil->store('fotos_perfil', 'public');
            $postulante->update(['foto_perfil' => $path]);
        }

        return $postulante;
    }

    /**
     * Crear postulaciones para múltiples ofertas
     */
    public function crearPostulaciones(Postulante $postulante, array $ofertaIds): array
    {
        $creadas = [];
        $duplicadas = [];

        foreach ($ofertaIds as $ofertaId) {
            $existe = Postulacion::where('postulante_id', $postulante->id)
                ->where('oferta_id', $ofertaId)
                ->exists();

            if ($existe) {
                $duplicadas[] = $ofertaId;
                continue;
            }

            $postulacion = Postulacion::create([
                'postulante_id' => $postulante->id,
                'oferta_id' => $ofertaId,
                'estado' => 'pendiente',
            ]);

            $creadas[] = $postulacion;
        }

        return [
            'creadas' => $creadas,
            'duplicadas' => $duplicadas,
        ];
    }

    /**
     * Guardar documentos del postulante
     */
    public function guardarDocumentos(Postulante $postulante, array $documentos): void
    {
        foreach ($documentos as $doc) {
            if (!isset($doc['tipo_documento_id']) || !isset($doc['archivo'])) {
                continue;
            }

            $archivo = $doc['archivo'];
            $path = $archivo->store('documentos_postulante/' . $postulante->ci, 'public');

            DocumentoPostulante::updateOrCreate(
                [
                    'postulante_id' => $postulante->id,
                    'tipo_documento_id' => $doc['tipo_documento_id'],
                ],
                ['archivo_pdf' => $path]
            );
        }
    }

    /**
     * Proceso completo de postulación
     */
    public function procesarPostulacionCompleta(array $datos): array
    {
        return DB::transaction(function () use ($datos) {
            // 1. Registrar postulante
            $postulante = $this->registrarPostulante(
                $datos,
                $datos['foto_perfil'] ?? null
            );

            // 2. Guardar expediente
            if (!empty($datos['formaciones'])) {
                $this->expedienteService->guardarFormaciones($postulante, $datos['formaciones']);
            }
            if (!empty($datos['experiencias'])) {
                $this->expedienteService->guardarExperiencias($postulante, $datos['experiencias']);
            }
            if (!empty($datos['capacitaciones'])) {
                $this->expedienteService->guardarCapacitaciones($postulante, $datos['capacitaciones']);
            }
            if (!empty($datos['producciones'])) {
                $this->expedienteService->guardarProducciones($postulante, $datos['producciones']);
            }
            if (!empty($datos['reconocimientos'])) {
                $this->expedienteService->guardarReconocimientos($postulante, $datos['reconocimientos']);
            }

            // 3. Guardar documentos requeridos
            if (!empty($datos['documentos'])) {
                $this->guardarDocumentos($postulante, $datos['documentos']);
            }

            // 4. Crear postulaciones
            $resultado = $this->crearPostulaciones($postulante, $datos['ofertas'] ?? []);

            return [
                'postulante' => $postulante,
                'postulaciones_creadas' => count($resultado['creadas']),
                'postulaciones_duplicadas' => count($resultado['duplicadas']),
            ];
        });
    }
}
