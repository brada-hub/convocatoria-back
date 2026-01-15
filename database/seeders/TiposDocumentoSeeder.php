<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoDocumento;
use Illuminate\Support\Str;

class TiposDocumentoSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiamos los tipos anteriores para tener solo los solicitados
        TipoDocumento::query()->delete();

        $tipos = [
            // II. FORMACIÓN ACADÉMICA
            [
                'nombre' => 'FORMACIÓN ACADÉMICA',
                'descripcion' => 'FORMACIÓN A NIVEL DE LICENCIATURA',
                'categoria' => 'academico',
                'permite_multiples' => true,
                'icono' => 'school',
                'orden' => 1,
                'campos' => [
                    ['nombre' => 'nivel', 'label' => 'Nivel (Ej. Licenciatura)', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'universidad', 'label' => 'Universidad', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'profesion', 'label' => 'Profesión (Nombre del título)', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'fecha_diploma', 'label' => 'Fecha de Diploma Académico', 'tipo' => 'date', 'obligatorio' => true],
                    ['nombre' => 'fecha_titulo', 'label' => 'Fecha de Título Profesional', 'tipo' => 'date', 'obligatorio' => true],
                ],
            ],
            // III. FORMACIÓN EN POSGRADO
            [
                'nombre' => 'FORMACIÓN EN POSGRADO',
                'descripcion' => 'PROGRAMAS CURSADOS DE DIPLOMADO, ESPECIALIDAD, MAESTRÍA, DOCTORADO O POSDOCTORADO',
                'categoria' => 'academico',
                'permite_multiples' => true,
                'icono' => 'workspace_premium',
                'orden' => 2,
                'campos' => [
                    ['nombre' => 'tipo_posgrado', 'label' => 'Tipo de Posgrado', 'tipo' => 'select', 'obligatorio' => true,
                     'opciones' => ['Diplomado', 'Especialidad', 'Maestría', 'Doctorado', 'Posdoctorado']],
                    ['nombre' => 'nombre_programa', 'label' => 'Nombre del programa', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'fecha_certificacion', 'label' => 'Fecha de certificación', 'tipo' => 'date', 'obligatorio' => true],
                    ['nombre' => 'institucion', 'label' => 'Institución/Universidad', 'tipo' => 'text', 'obligatorio' => true],
                ],
            ],
            // IV. EXPERIENCIA EN DOCENCIA UNIVERSITARIA
            [
                'nombre' => 'EXPERIENCIA EN DOCENCIA UNIVERSITARIA',
                'descripcion' => 'UNIVERSIDADES DEL SISTEMA PÚBLICO O PRIVADO',
                'categoria' => 'laboral',
                'permite_multiples' => true,
                'icono' => 'history_edu',
                'orden' => 3,
                'campos' => [
                    ['nombre' => 'universidad', 'label' => 'Universidad', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'carrera', 'label' => 'Carrera(s)', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'asignaturas', 'label' => 'Asignatura(s) o Materia(s)', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'gestion_periodo', 'label' => 'Gestión o Periodo académico', 'tipo' => 'text', 'obligatorio' => true],
                ],
            ],
            // V. EXPERIENCIAS PROFESIONALES
            [
                'nombre' => 'EXPERIENCIAS PROFESIONALES',
                'descripcion' => 'FUNCIONES DESEMPEÑADAS CORRESPONDIENTES A SU ÁREA DE FORMACIÓN',
                'categoria' => 'laboral',
                'permite_multiples' => true,
                'icono' => 'work',
                'orden' => 4,
                'campos' => [
                    ['nombre' => 'institucion_empresa', 'label' => 'Institución/Empresa', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'cargo_ocupado', 'label' => 'Cargo ocupado', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'fecha_inicio', 'label' => 'Fecha de inicio', 'tipo' => 'date', 'obligatorio' => true],
                    ['nombre' => 'fecha_conclusion', 'label' => 'Fecha de conclusión', 'tipo' => 'date', 'obligatorio' => false],
                ],
            ],
            // VI. CAPACITACIONES ADICIONALES
            [
                'nombre' => 'CAPACITACIONES ADICIONALES',
                'descripcion' => 'CURSOS DE FORMACIÓN CONTINUA, TALLERES U OTROS MAYORES A 40 HORAS',
                'categoria' => 'capacitacion',
                'permite_multiples' => true,
                'icono' => 'menu_book',
                'orden' => 5,
                'campos' => [
                    ['nombre' => 'nombre_programa', 'label' => 'Nombre o Título del programa', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'fecha_capacitacion', 'label' => 'Fecha de capacitación', 'tipo' => 'date', 'obligatorio' => true],
                    ['nombre' => 'institucion_organizadora', 'label' => 'Institución organizadora', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'carga_horaria', 'label' => 'Carga horaria (en horas)', 'tipo' => 'number', 'obligatorio' => true],
                ],
            ],
            // VII. PRODUCCIÓN INTELECTUAL
            [
                'nombre' => 'PRODUCCIÓN INTELECTUAL',
                'descripcion' => 'LIBROS, ARTÍCULOS, GUÍAS DE ESTUDIO, TESIS DE MAESTRÍA Y/O DOCTORADO',
                'categoria' => 'produccion',
                'permite_multiples' => true,
                'icono' => 'auto_stories',
                'orden' => 6,
                'campos' => [
                    ['nombre' => 'tipo_publicacion', 'label' => 'Tipo de publicación', 'tipo' => 'select', 'obligatorio' => true,
                     'opciones' => ['Libro', 'Artículo Indexado', 'Artículo no Indexado', 'Guía de estudio', 'Tesis']],
                    ['nombre' => 'titulo_documento', 'label' => 'Título del documento', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'fecha_publicacion', 'label' => 'Fecha de publicación', 'tipo' => 'date', 'obligatorio' => true],
                    ['nombre' => 'editorial', 'label' => 'Editorial', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'lugar', 'label' => 'Lugar', 'tipo' => 'text', 'obligatorio' => true],
                ],
            ],
            // VIII. RECONOCIMIENTOS Y PREMIACIONES
            [
                'nombre' => 'RECONOCIMIENTOS Y PREMIACIONES',
                'descripcion' => 'DOCTORADOS HONORIS CAUSA, PARTICIPACIÓN EN CONGRESOS, ETC.',
                'categoria' => 'reconocimiento',
                'permite_multiples' => true,
                'icono' => 'emoji_events',
                'orden' => 7,
                'campos' => [
                    ['nombre' => 'titulo_reconocimiento', 'label' => 'Título del reconocimiento', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'fecha_reconocimiento', 'label' => 'Fecha del reconocimiento', 'tipo' => 'date', 'obligatorio' => true],
                    ['nombre' => 'institucion_otorgante', 'label' => 'Institución otorgante', 'tipo' => 'text', 'obligatorio' => true],
                    ['nombre' => 'lugar', 'label' => 'Lugar', 'tipo' => 'text', 'obligatorio' => true],
                ],
            ],
            // Documentos Personales fijos (Se usan para filtrado en frontend)
            [
                'nombre' => 'Cédula de Identidad',
                'descripcion' => 'Cédula de Identidad (Archivo PDF/JPG)',
                'categoria' => 'personal',
                'permite_multiples' => false,
                'icono' => 'badge',
                'orden' => 8,
                'campos' => [],
            ],
            [
                'nombre' => 'Carta de Postulación',
                'descripcion' => 'Carta de postulación (Archivo)',
                'categoria' => 'personal',
                'permite_multiples' => false,
                'icono' => 'mail',
                'orden' => 9,
                'campos' => [],
            ],
            [
                'nombre' => 'Curriculum Vitae',
                'descripcion' => 'Curriculum Vitae (Archivo)',
                'categoria' => 'personal',
                'permite_multiples' => false,
                'icono' => 'description',
                'orden' => 10,
                'campos' => [],
            ],
        ];

        foreach ($tipos as $tipo) {
            TipoDocumento::create([
                'nombre' => $tipo['nombre'],
                'slug' => Str::slug($tipo['nombre']),
                'descripcion' => $tipo['descripcion'],
                'categoria' => $tipo['categoria'],
                'permite_multiples' => $tipo['permite_multiples'],
                'icono' => $tipo['icono'],
                'orden' => $tipo['orden'] ?? 0,
                'campos' => $tipo['campos'],
                'activo' => true,
            ]);
        }

        $this->command->info('✅ Tipos de documento creados según requerimiento del usuario');
    }
}
