<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Catálogo de tipos de documento
        Schema::create('tipos_documento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Carta de Postulación, CV, etc.
            $table->string('descripcion')->nullable();
            $table->string('icono')->default('description'); // Icono de Material Icons
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        // 2. Documentos requeridos por convocatoria
        Schema::create('convocatoria_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained('convocatorias')->onDelete('cascade');
            $table->foreignId('tipo_documento_id')->constrained('tipos_documento')->onDelete('cascade');
            $table->boolean('obligatorio')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->unique(['convocatoria_id', 'tipo_documento_id'], 'conv_doc_unique');
        });

        // 3. Documentos subidos por postulante (por cada postulación)
        Schema::create('documentos_postulante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulante_id')->constrained('postulantes')->onDelete('cascade');
            $table->foreignId('tipo_documento_id')->constrained('tipos_documento')->onDelete('cascade');
            $table->string('archivo_pdf');
            $table->timestamps();

            $table->unique(['postulante_id', 'tipo_documento_id'], 'post_doc_unique');
        });

        // Insertar tipos de documento predefinidos
        DB::table('tipos_documento')->insert([
            [
                'nombre' => 'Carta de Postulación',
                'descripcion' => 'Carta dirigida al Rector expresando motivación y pretensión salarial',
                'icono' => 'mail',
                'orden' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Curriculum Vitae (CV)',
                'descripcion' => 'Hoja de vida actualizada del postulante',
                'icono' => 'article',
                'orden' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Plan de Gestión',
                'descripcion' => 'Breve propuesta de plan de gestión para el cargo',
                'icono' => 'task',
                'orden' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Diploma Académico / Título Profesional',
                'descripcion' => 'Título de licenciatura en el área correspondiente',
                'icono' => 'school',
                'orden' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Diplomado en Educación Superior',
                'descripcion' => 'Certificado de diplomado en educación superior',
                'icono' => 'workspace_premium',
                'orden' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Diploma de Maestría',
                'descripcion' => 'Título de maestría o grado superior',
                'icono' => 'military_tech',
                'orden' => 6,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Cédula de Identidad',
                'descripcion' => 'Fotocopia de CI, anverso y reverso en una sola plana',
                'icono' => 'badge',
                'orden' => 7,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nombre' => 'Certificados de Experiencia Laboral',
                'descripcion' => 'Documentación que demuestre experiencia laboral',
                'icono' => 'work_history',
                'orden' => 8,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('documentos_postulante');
        Schema::dropIfExists('convocatoria_documentos');
        Schema::dropIfExists('tipos_documento');
    }
};
