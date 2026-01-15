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
                'ci_expedido' => $datos['ci_expedido'] ?? null,
                'nacionalidad' => $datos['nacionalidad'] ?? null,
                'direccion' => $datos['direccion'] ?? null,
            ]
        );

        // Process profile photo if provided
        if ($fotoPerfil) {
            $path = $fotoPerfil->store('fotos_perfil', 'public');
            $postulante->update(['foto_perfil' => $path]);
        }

        // Process carta de postulación if provided
        if (isset($datos['carta_postulacion']) && $datos['carta_postulacion'] instanceof \Illuminate\Http\UploadedFile) {
            $path = $datos['carta_postulacion']->store('documentos_postulante/' . $postulante->ci, 'public');
            $postulante->update(['carta_postulacion_pdf' => $path]);
        }

        // Process curriculum vitae if provided
        if (isset($datos['curriculum_vitae']) && $datos['curriculum_vitae'] instanceof \Illuminate\Http\UploadedFile) {
            $path = $datos['curriculum_vitae']->store('documentos_postulante/' . $postulante->ci, 'public');
            $postulante->update(['curriculum_vitae_pdf' => $path]);
        }

        // Process documento CI if provided
        if (isset($datos['ci_documento']) && $datos['ci_documento'] instanceof \Illuminate\Http\UploadedFile) {
            $path = $datos['ci_documento']->store('documentos_postulante/' . $postulante->ci, 'public');
            $postulante->update(['ci_documento_pdf' => $path]);
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
        // Limpiar documentos anteriores de los tipos que se están enviando
        // para evitar duplicados si el usuario re-envía el formulario
        $tiposEnviados = array_unique(array_column($documentos, 'tipo_documento_id'));
        DocumentoPostulante::where('postulante_id', $postulante->id)
            ->whereIn('tipo_documento_id', $tiposEnviados)
            ->delete();

        foreach ($documentos as $doc) {
            if (!isset($doc['tipo_documento_id']) || !isset($doc['archivo'])) {
                continue;
            }

            $archivo = $doc['archivo'];
            $path = $archivo->store('documentos_postulante/' . $postulante->ci, 'public');

            $metadatos = null;
            if (isset($doc['metadatos'])) {
                $metadatos = is_string($doc['metadatos']) ? json_decode($doc['metadatos'], true) : $doc['metadatos'];
            }

            DocumentoPostulante::create([
                'postulante_id' => $postulante->id,
                'tipo_documento_id' => $doc['tipo_documento_id'],
                'archivo_pdf' => $path,
                'metadatos' => $metadatos
            ]);
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
