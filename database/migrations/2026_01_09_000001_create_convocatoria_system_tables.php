<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Convocatorias
        Schema::create('convocatorias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('sede');
            $table->string('carrera');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // 2. Postulantes (Identificados por CI)
        Schema::create('postulantes', function (Blueprint $table) {
            $table->id();
            $table->string('ci')->unique();
            $table->string('nombre_completo');
            $table->string('email');
            $table->string('telefono');
            $table->timestamps();
        });

        // 3. Postulaciones (Vínculo entre convocatoria y postulante)
        Schema::create('postulaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulante_id')->constrained('postulantes');
            $table->foreignId('convocatoria_id')->constrained('convocatorias');
            $table->enum('estado', ['en_espera', 'verificado', 'observado', 'rechazado'])->default('en_espera');
            $table->timestamps();
        });

        // 4. Formación Académica
        Schema::create('formaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulante_id')->constrained('postulantes');
            $table->enum('tipo', ['pregrado', 'postgrado']);
            $table->string('titulo');
            $table->string('universidad');
            $table->integer('anio_emision');
            $table->string('archivo_respaldo')->nullable();
            $table->timestamps();
        });

        // 5. Experiencia
        Schema::create('experiencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulante_id')->constrained('postulantes');
            $table->enum('tipo', ['docente', 'profesional']);
            $table->string('cargo');
            $table->string('institucion');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('archivo_respaldo')->nullable();
            $table->timestamps();
        });

        // 6. Capacitaciones
        Schema::create('capacitaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulante_id')->constrained('postulantes');
            $table->string('nombre_curso');
            $table->string('institucion');
            $table->integer('carga_horaria');
            $table->string('archivo_respaldo')->nullable();
            $table->timestamps();
        });

        // 7. Reconocimientos
        Schema::create('reconocimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulante_id')->constrained('postulantes');
            $table->string('titulo_premio');
            $table->integer('anio');
            $table->string('archivo_respaldo')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reconocimientos');
        Schema::dropIfExists('capacitaciones');
        Schema::dropIfExists('experiencias');
        Schema::dropIfExists('formaciones');
        Schema::dropIfExists('postulaciones');
        Schema::dropIfExists('postulantes');
        Schema::dropIfExists('convocatorias');
    }
};
