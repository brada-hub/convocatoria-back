<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NivelAcademico;

class NivelAcademicoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $niveles = [
            [
                'nombre' => 'Licenciatura',
                'slug' => 'licenciatura',
                'activo' => true
            ],
            [
                'nombre' => 'Maestría',
                'slug' => 'maestria',
                'activo' => true
            ],
            [
                'nombre' => 'Doctorado',
                'slug' => 'doctorado',
                'activo' => true
            ],
            [
                'nombre' => 'Diplomado',
                'slug' => 'diplomado',
                'activo' => true
            ],
            [
                'nombre' => 'Especialidad',
                'slug' => 'especialidad',
                'activo' => true
            ],
            [
                'nombre' => 'Cursos Adicionales',
                'slug' => 'Cursos aprobados con carga horaria',
                'activo' => true
            ],
        ];

        foreach ($niveles as $nivel) {
            NivelAcademico::updateOrCreate(
                ['nombre' => $nivel['nombre']],
                $nivel
            );
        }
    }
}
