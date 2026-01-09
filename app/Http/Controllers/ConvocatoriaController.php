<?php

namespace App\Http\Controllers;

use App\Models\Convocatoria;
use App\Models\Postulante;
use App\Models\Postulacion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConvocatoriaController extends Controller
{
    // Public: Check CI and status
    public function checkCI(Request $request)
    {
        $request->validate(['ci' => 'required', 'slug' => 'required']);

        $convocatoria = Convocatoria::where('slug', $request->slug)->firstOrFail();
        $postulante = Postulante::where('ci', $request->ci)->first();

        if (!$postulante) {
            return response()->json(['status' => 'new', 'message' => 'Postulante no registrado']);
        }

        $postulacion = Postulacion::where('postulante_id', $postulante->id)
                                 ->where('convocatoria_id', $convocatoria->id)
                                 ->first();

        if ($postulacion) {
            return response()->json([
                'status' => 'exists',
                'estado' => $postulacion->estado,
                'postulante' => $postulante->load(['formaciones', 'experiencias', 'capacitaciones', 'reconocimientos'])
            ]);
        }

        return response()->json(['status' => 'ready', 'postulante' => $postulante]);
    }

    // Admin: Store Convocatoria
    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => 'required',
            'sede' => 'required',
            'carrera' => 'required',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        $data['slug'] = Str::slug($data['titulo'] . '-' . Str::random(5));
        $convocatoria = Convocatoria::create($data);

        return response()->json($convocatoria);
    }

    // Admin: List all postulations
    public function indexPostulaciones()
    {
        return Postulacion::with(['postulante', 'convocatoria'])->get();
    }
}
