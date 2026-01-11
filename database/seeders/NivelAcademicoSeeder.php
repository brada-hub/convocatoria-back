<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NivelAcademico;
use App\Models\Formacion;

class NivelAcademicoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Formacion::NIVELES as $slug => $nombre) {
            NivelAcademico::firstOrCreate(
                ['slug' => $slug], // Evitar duplicados
                [
                    'nombre' => $nombre,
                    'activo' => true
                ]
            );
        }
    }
}
