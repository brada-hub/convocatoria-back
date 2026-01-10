<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar índices para mejorar rendimiento en búsquedas frecuentes
     */
    public function up()
    {
        // Índices en postulantes
        Schema::table('postulantes', function (Blueprint $table) {
            $table->index('ci');
            $table->index('email');
        });

        // Índices en convocatorias
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->index('estado');
            $table->index('fecha_cierre');
            $table->index(['estado', 'fecha_cierre']); // Índice compuesto para convocatorias abiertas
        });

        // Índices en postulaciones
        Schema::table('postulaciones', function (Blueprint $table) {
            $table->index('estado');
            $table->index('created_at');
        });

        // Índices en ofertas (convocatoria_sede_cargo)
        Schema::table('convocatoria_sede_cargo', function (Blueprint $table) {
            $table->index('activo');
        });
    }

    public function down()
    {
        Schema::table('postulantes', function (Blueprint $table) {
            $table->dropIndex(['ci']);
            $table->dropIndex(['email']);
        });

        Schema::table('convocatorias', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropIndex(['fecha_cierre']);
            $table->dropIndex(['estado', 'fecha_cierre']);
        });

        Schema::table('postulaciones', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('convocatoria_sede_cargo', function (Blueprint $table) {
            $table->dropIndex(['activo']);
        });
    }
};
