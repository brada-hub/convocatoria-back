<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Eliminar tablas anteriores si existen
        Schema::dropIfExists('reconocimientos');
        Schema::dropIfExists('producciones');
        Schema::dropIfExists('capacitaciones');
        Schema::dropIfExists('experiencias');
        Schema::dropIfExists('formaciones');
        Schema::dropIfExists('postulaciones');
        Schema::dropIfExists('convocatoria_sede_cargo');
        Schema::dropIfExists('postulantes');
        Schema::dropIfExists('convocatorias');
        Schema::dropIfExists('cargos');
        Schema::dropIfExists('sedes');

        // 1. SEDES - Ubicaciones Físicas
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Sede Central, Sede Sur, Sede Virtual
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 2. CARGOS - Puestos disponibles
        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Telemarketing, Director de Carrera, Recepcionista
            $table->text('requisitos')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 3. CONVOCATORIAS - Periodos de postulación
        Schema::create('convocatorias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo'); // Convocatoria Enero 2026
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_cierre');
            $table->string('slug')->unique();
            $table->enum('estado', ['borrador', 'activa', 'cerrada', 'finalizada'])->default('borrador');
            $table->timestamps();
        });

        // 4. OFERTA - Mapeo de Cargos por Sede en cada Convocatoria
        Schema::create('convocatoria_sede_cargo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained('convocatorias')->onDelete('cascade');
            $table->foreignId('sede_id')->constrained('sedes')->onDelete('cascade');
            $table->foreignId('cargo_id')->constrained('cargos')->onDelete('cascade');
            $table->integer('vacantes')->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Evitar duplicados
            $table->unique(['convocatoria_id', 'sede_id', 'cargo_id'], 'convocatoria_sede_cargo_unique');
        });

        // 5. POSTULANTES - Datos de los candidatos
        Schema::create('postulantes', function (Blueprint $table) {
            $table->id();
            $table->string('ci')->unique(); // Carnet de Identidad
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('email')->nullable();
            $table->string('celular');
            $table->string('foto_perfil')->nullable(); // Ruta de la foto
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('genero', ['M', 'F', 'Otro'])->nullable();
            $table->timestamps();
        });

        // 6. POSTULACIONES - Vínculo postulante con ofertas
        Schema::create('postulaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulante_id')->constrained('postulantes')->onDelete('cascade');
            $table->foreignId('oferta_id')->constrained('convocatoria_sede_cargo')->onDelete('cascade');
            $table->enum('estado', [
                'pendiente',      // Recién enviado
                'en_revision',    // Siendo evaluado
                'observado',      // Requiere correcciones
                'habilitado',     // Habilitado para entrevista
                'rechazado',      // No cumple requisitos
                'seleccionado'    // Seleccionado para el puesto
            ])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Un postulante solo puede postular una vez a cada oferta
            $table->unique(['postulante_id', 'oferta_id'], 'postulacion_unica');
        });

        // 7. FORMACIONES ACADÉMICAS
        Schema::create('formaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulante_id')->constrained('postulantes')->onDelete('cascade');
            $table->enum('nivel', ['licenciatura', 'maestria', 'doctorado', 'diplomado', 'especialidad']);
            $table->string('titulo_profesion');
            $table->string('universidad');
            $table->year('anio_emision');
            $table->string('archivo_pdf')->nullable();
            $table->timestamps();
        });

        // 8. EXPERIENCIAS PROFESIONALES (Últimos 5 años)
        Schema::create('experiencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulante_id')->constrained('postulantes')->onDelete('cascade');
            $table->string('cargo_desempenado');
            $table->string('empresa_institucion');
            $table->year('anio_inicio');
            $table->year('anio_fin')->nullable(); // Null = trabajo actual
            $table->text('funciones')->nullable();
            $table->string('archivo_pdf')->nullable();
            $table->timestamps();
        });

        // 9. CAPACITACIONES Y CURSOS
        Schema::create('capacitaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulante_id')->constrained('postulantes')->onDelete('cascade');
            $table->string('nombre_curso');
            $table->string('institucion_emisora');
            $table->integer('carga_horaria')->nullable();
            $table->year('anio');
            $table->string('archivo_pdf')->nullable();
            $table->timestamps();
        });

        // 10. PRODUCCIÓN INTELECTUAL / PROYECTOS
        Schema::create('producciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulante_id')->constrained('postulantes')->onDelete('cascade');
            $table->enum('tipo', ['libro', 'articulo', 'software', 'investigacion', 'proyecto', 'otro']);
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->year('anio');
            $table->string('archivo_pdf')->nullable();
            $table->timestamps();
        });

        // 11. RECONOCIMIENTOS Y PREMIACIONES
        Schema::create('reconocimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulante_id')->constrained('postulantes')->onDelete('cascade');
            $table->string('tipo_reconocimiento');
            $table->string('titulo');
            $table->string('otorgado_por')->nullable();
            $table->year('anio');
            $table->string('archivo_pdf')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reconocimientos');
        Schema::dropIfExists('producciones');
        Schema::dropIfExists('capacitaciones');
        Schema::dropIfExists('experiencias');
        Schema::dropIfExists('formaciones');
        Schema::dropIfExists('postulaciones');
        Schema::dropIfExists('convocatoria_sede_cargo');
        Schema::dropIfExists('postulantes');
        Schema::dropIfExists('convocatorias');
        Schema::dropIfExists('cargos');
        Schema::dropIfExists('sedes');
    }
};
