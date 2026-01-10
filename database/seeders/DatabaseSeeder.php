<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Sede;
use App\Models\Cargo;
use App\Models\Convocatoria;
use App\Models\Oferta;
use App\Models\Rol;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==============================
        // ROLES
        // ==============================
        $adminRol = Rol::updateOrCreate(['nombre' => 'Administrador'], ['descripcion' => 'Acceso total al sistema', 'activo' => true]);
        $userRol = Rol::updateOrCreate(['nombre' => 'Usuario'], ['descripcion' => 'Acceso limitado', 'activo' => true]);

        // ==============================
        // USUARIO ADMINISTRADOR
        // ==============================
        User::updateOrCreate(
            ['email' => 'admin@sistema.com'], // Still using email as unique key for seeding idempotency? Or CI? Let's use CI.
            [
                'nombres' => 'Administrador',
                'apellidos' => 'Sistema',
                'ci' => 'admin', // Dummy CI for admin
                'rol_id' => $adminRol->id,
                'password' => Hash::make('admin123'),
                'activo' => true,
                'must_change_password' => false,
            ]
        );

        // ==============================
        // SEDES
        // ==============================
        $sedes = [
            ['nombre' => 'La Paz'],
            ['nombre' => 'Oruro'],
            ['nombre' => 'Potosí'],
            ['nombre' => 'Cochabamba'],
            ['nombre' => 'Chuquisaca'],
            ['nombre' => 'Tarija'],
            ['nombre' => 'Pando'],
            ['nombre' => 'Beni'],
            ['nombre' => 'Santa Cruz'],
            ['nombre' => 'Bolivia'], // Opción Nacional
        ];

        foreach ($sedes as $sede) {
            Sede::updateOrCreate(['nombre' => $sede['nombre']], $sede);
        }

        // ==============================
        // CARGOS
        // ==============================
        $cargos = [
            [
                'nombre' => 'Docente de Matemáticas',
                'requisitos' => 'Licenciatura en Matemáticas o Ingeniería. Experiencia mínima 2 años.',
            ],
            [
                'nombre' => 'Docente de Programación',
                'requisitos' => 'Licenciatura en Sistemas o afín. Experiencia en desarrollo.',
            ],
            [
                'nombre' => 'Docente de Idiomas',
                'requisitos' => 'Licenciatura en Lingüística. Certificación de idiomas.',
            ],
            [
                'nombre' => 'Director de Carrera',
                'requisitos' => 'Maestría. Experiencia docente mínima 5 años.',
            ],
            [
                'nombre' => 'Coordinador Académico',
                'requisitos' => 'Licenciatura. Experiencia en gestión académica.',
            ],
            [
                'nombre' => 'Telemarketing',
                'requisitos' => 'Bachiller o técnico. Habilidades de comunicación.',
            ],
            [
                'nombre' => 'Recepcionista',
                'requisitos' => 'Bachiller. Buen trato al público.',
            ],
            [
                'nombre' => 'Soporte Técnico',
                'requisitos' => 'Técnico medio o superior en sistemas.',
            ],
            [
                'nombre' => 'Auxiliar Administrativo',
                'requisitos' => 'Bachiller. Conocimientos de ofimática.',
            ],
            [
                'nombre' => 'Diseñador Gráfico',
                'requisitos' => 'Licenciatura en Diseño Gráfico. Portafolio.',
            ],
        ];

        foreach ($cargos as $cargo) {
            Cargo::updateOrCreate(['nombre' => $cargo['nombre']], $cargo);
        }

        // ==============================
        // CONVOCATORIA DE EJEMPLO
        // ==============================
        $convocatoria = Convocatoria::updateOrCreate(
            ['slug' => 'convocatoria-enero-2026'],
            [
                'titulo' => 'Convocatoria Enero 2026',
                'descripcion' => 'Convocatoria para cubrir vacantes del primer trimestre 2026',
                'fecha_inicio' => '2026-01-01',
                'fecha_cierre' => '2026-01-31',
                'estado' => 'activa',
            ]
        );

        // Asignar ofertas (cargos en sedes)
        $laPaz = Sede::where('nombre', 'La Paz')->first();
        $santaCruz = Sede::where('nombre', 'Santa Cruz')->first();
        $cochabamba = Sede::where('nombre', 'Cochabamba')->first();

        $ofertas = [
            // La Paz
            ['sede' => $laPaz, 'cargo' => 'Docente de Matemáticas', 'vacantes' => 2],
            ['sede' => $laPaz, 'cargo' => 'Docente de Programación', 'vacantes' => 3],
            ['sede' => $laPaz, 'cargo' => 'Director de Carrera', 'vacantes' => 1],

            // Santa Cruz
            ['sede' => $santaCruz, 'cargo' => 'Docente de Idiomas', 'vacantes' => 2],
            ['sede' => $santaCruz, 'cargo' => 'Coordinador Académico', 'vacantes' => 1],

            // Cochabamba
            ['sede' => $cochabamba, 'cargo' => 'Docente de Programación', 'vacantes' => 4],
            ['sede' => $cochabamba, 'cargo' => 'Soporte Técnico', 'vacantes' => 2],
        ];

        foreach ($ofertas as $oferta) {
            $cargo = Cargo::where('nombre', $oferta['cargo'])->first();
            if ($cargo && $oferta['sede']) {
                Oferta::updateOrCreate(
                    [
                        'convocatoria_id' => $convocatoria->id,
                        'sede_id' => $oferta['sede']->id,
                        'cargo_id' => $cargo->id,
                    ],
                    ['vacantes' => $oferta['vacantes']]
                );
            }
        }

        // Segunda convocatoria (próxima)
        $convocatoria2 = Convocatoria::updateOrCreate(
            ['slug' => 'convocatoria-febrero-2026'],
            [
                'titulo' => 'Convocatoria Febrero 2026',
                'descripcion' => 'Convocatoria para personal administrativo',
                'fecha_inicio' => '2026-02-01',
                'fecha_cierre' => '2026-02-28',
                'estado' => 'borrador',
            ]
        );

        // Información de seed completado
        $this->command->info('✅ Datos de prueba creados exitosamente:');
        $this->command->info('   - Usuario admin: admin@sistema.com / admin123');
        $this->command->info('   - ' . Sede::count() . ' sedes');
        $this->command->info('   - ' . Cargo::count() . ' cargos');
        $this->command->info('   - ' . Convocatoria::count() . ' convocatorias');
        $this->command->info('   - ' . Oferta::count() . ' ofertas (cargos en sedes)');
    }
}
