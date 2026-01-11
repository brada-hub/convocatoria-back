<?php

namespace App\Http\Controllers;

use App\Models\NivelAcademico;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NivelAcademicoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return NivelAcademico::orderBy('id', 'desc')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:niveles_academicos,slug'
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['nombre']);
        }

        $nivel = NivelAcademico::create($data);

        return response()->json($nivel, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(NivelAcademico $nivelAcademico)
    {
        return $nivelAcademico;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, NivelAcademico $nivelAcademico)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:niveles_academicos,slug,' . $nivelAcademico->id,
            'activo' => 'boolean'
        ]);

        // Si cambia el nombre y no envían slug, podríamos regenerarlo,
        // pero mejor respetar el existente salvo que lo cambien explícitamente
        // para no romper referencias históricas.

        $nivelAcademico->update($data);

        return response()->json($nivelAcademico);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NivelAcademico $nivelAcademico)
    {
        $nivelAcademico->delete();
        return response()->json(['message' => 'Eliminado exitosamente']);
    }

    public function toggle(NivelAcademico $nivelAcademico)
    {
        $nivelAcademico->activo = !$nivelAcademico->activo;
        $nivelAcademico->save();
        return response()->json($nivelAcademico);
    }
}
