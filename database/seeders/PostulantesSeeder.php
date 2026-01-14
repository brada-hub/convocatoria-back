<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Postulante;
use App\Models\Postulacion;
use App\Models\Oferta;
use App\Models\Formacion;
use App\Models\Experiencia;
use Faker\Factory as Faker;

class PostulantesSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES');

        // Lista de nombres y apellidos bolivianos típicos
        $nombres = [
            'Juan Carlos', 'María Elena', 'Luis Alberto', 'Ana María', 'Carlos Eduardo',
            'Patricia', 'Roberto', 'Carmen Rosa', 'Jorge Luis', 'Sandra Paola',
            'Miguel Ángel', 'Gabriela', 'Fernando', 'Rosa María', 'Andrés',
            'Claudia Marcela', 'Oscar Javier', 'Mónica', 'Hugo René', 'Silvia Lorena',
            'Diego Armando', 'Verónica', 'Ramiro', 'Carla Daniela', 'Nelson',
        ];

        $apellidos = [
            'Mamani Quispe', 'Condori Choque', 'García López', 'Rodríguez Fernández', 'Flores Ticona',
            'Quispe Huanca', 'Choque Mamani', 'López Sánchez', 'Fernández García', 'Ticona Condori',
            'Huanca Flores', 'Sánchez Mendoza', 'Mendoza Vargas', 'Vargas Rojas', 'Rojas Nina',
            'Nina Apaza', 'Apaza Calle', 'Calle Limachi', 'Limachi Yujra', 'Yujra Poma',
            'Poma Cruz', 'Cruz Torrez', 'Torrez Ramos', 'Ramos Gutiérrez', 'Gutiérrez Silva',
        ];

        $universidades = [
            'Universidad Mayor de San Andrés (UMSA)',
            'Universidad Mayor de San Simón (UMSS)',
            'Universidad Autónoma Gabriel René Moreno (UAGRM)',
            'Universidad Católica Boliviana',
            'Universidad Técnica Privada Cosmos (UNITEPC)',
            'Universidad Privada Boliviana (UPB)',
            'Universidad NUR',
            'Universidad Loyola',
        ];

        $titulos = [
            'Licenciatura en Ingeniería de Sistemas',
            'Licenciatura en Matemáticas',
            'Licenciatura en Lingüística',
            'Licenciatura en Administración de Empresas',
            'Maestría en Educación Superior',
            'Licenciatura en Pedagogía',
            'Ingeniería Comercial',
            'Licenciatura en Ciencias de la Comunicación',
        ];

        $empresas = [
            'UNITEPC',
            'Universidad Mayor de San Andrés',
            'Colegio Nacional Ayacucho',
            'Instituto Tecnológico Bolivia Mar',
            'Empresa Nacional de Telecomunicaciones',
            'Banco Mercantil Santa Cruz',
            'Caja de Salud de la Banca Privada',
            'YPFB Corporación',
            'Mi Teleférico',
            'Fancesa S.A.',
        ];

        $cargosExperiencia = [
            'Docente de Matemáticas',
            'Docente de Programación',
            'Coordinador de Área',
            'Director Académico',
            'Asistente Administrativo',
            'Analista de Sistemas',
            'Técnico de Soporte',
            'Recepcionista',
            'Jefe de Personal',
            'Diseñador Gráfico',
        ];

        // Obtener todas las ofertas disponibles
        $ofertas = Oferta::with(['convocatoria', 'sede', 'cargo'])->get();

        if ($ofertas->isEmpty()) {
            $this->command->warn('⚠️ No hay ofertas disponibles. Ejecuta primero DatabaseSeeder.');
            return;
        }

        $estados = ['pendiente', 'en_revision', 'observado', 'habilitado', 'rechazado', 'seleccionado'];

        $postulantesCreados = 0;
        $postulacionesCreadas = 0;

        // Crear 15 postulantes con datos variados
        for ($i = 0; $i < 15; $i++) {
            $nombre = $nombres[array_rand($nombres)];
            $apellido = $apellidos[array_rand($apellidos)];
            $ci = $faker->numberBetween(1000000, 9999999);

            // Crear postulante
            $postulante = Postulante::create([
                'ci' => (string) $ci,
                'nombres' => $nombre,
                'apellidos' => $apellido,
                'email' => strtolower(str_replace(' ', '.', $nombre)) . '.' . strtolower(explode(' ', $apellido)[0]) . '@gmail.com',
                'celular' => '7' . $faker->numberBetween(1000000, 9999999),
                'fecha_nacimiento' => $faker->dateTimeBetween('-50 years', '-22 years')->format('Y-m-d'),
                'genero' => $faker->randomElement(['M', 'F']),
            ]);

            $postulantesCreados++;

            // Agregar formación académica (1-3 registros)
            $numFormaciones = $faker->numberBetween(1, 3);
            for ($f = 0; $f < $numFormaciones; $f++) {
                Formacion::create([
                    'postulante_id' => $postulante->id,
                    'nivel' => $faker->randomElement(['licenciatura', 'maestria', 'doctorado', 'diplomado', 'especialidad']),
                    'titulo_profesion' => $titulos[array_rand($titulos)],
                    'universidad' => $universidades[array_rand($universidades)],
                    'anio_emision' => $faker->numberBetween(2010, 2024),
                ]);
            }

            // Agregar experiencia laboral (1-4 registros)
            $numExperiencias = $faker->numberBetween(1, 4);
            for ($e = 0; $e < $numExperiencias; $e++) {
                $anioInicio = $faker->numberBetween(2015, 2022);
                Experiencia::create([
                    'postulante_id' => $postulante->id,
                    'cargo_desempenado' => $cargosExperiencia[array_rand($cargosExperiencia)],
                    'empresa_institucion' => $empresas[array_rand($empresas)],
                    'anio_inicio' => $anioInicio,
                    'anio_fin' => $faker->optional(0.7)->numberBetween($anioInicio + 1, 2025),
                    'funciones' => $faker->sentence(10),
                ]);
            }

            // Crear 1-2 postulaciones por postulante a ofertas aleatorias
            $numPostulaciones = $faker->numberBetween(1, 2);
            $ofertasRandom = $ofertas->random(min($numPostulaciones, $ofertas->count()));

            foreach ($ofertasRandom as $oferta) {
                // Evitar duplicados
                $exists = Postulacion::where('postulante_id', $postulante->id)
                    ->where('oferta_id', $oferta->id)
                    ->exists();

                if (!$exists) {
                    Postulacion::create([
                        'postulante_id' => $postulante->id,
                        'oferta_id' => $oferta->id,
                        'estado' => $estados[array_rand($estados)],
                        'observaciones' => $faker->optional(0.3)->sentence(6),
                    ]);
                    $postulacionesCreadas++;
                }
            }
        }

        $this->command->info("✅ Postulantes de prueba creados:");
        $this->command->info("   - {$postulantesCreados} postulantes con expedientes");
        $this->command->info("   - {$postulacionesCreadas} postulaciones a ofertas");
    }
}
