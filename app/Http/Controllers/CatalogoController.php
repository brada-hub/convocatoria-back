<?php

namespace App\Http\Controllers;

use App\Models\Formacion;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    /**
     * Obtener niveles académicos disponibles
     */
    public function nivelesAcademicos()
    {
        $niveles = \App\Models\NivelAcademico::where('activo', true)
            ->get()
            ->map(function ($nivel) {
                return [
                    'label' => $nivel->nombre,
                    'value' => $nivel->slug
                ];
            });

        return response()->json($niveles);
    }
}
