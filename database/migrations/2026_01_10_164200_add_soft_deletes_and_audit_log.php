<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar soft deletes a tablas principales y crear tabla de auditoría
     */
    public function up()
    {
        // 1. Agregar soft deletes a postulantes
        Schema::table('postulantes', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 2. Agregar soft deletes a convocatorias
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 3. Agregar soft deletes a postulaciones
        Schema::table('postulaciones', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 4. Agregar soft deletes a sedes
        Schema::table('sedes', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 5. Agregar soft deletes a cargos
        Schema::table('cargos', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 6. Crear tabla de auditoría
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type'); // Modelo afectado
            $table->unsignedBigInteger('auditable_id'); // ID del registro
            $table->string('event'); // created, updated, deleted, restored
            $table->json('old_values')->nullable(); // Valores anteriores
            $table->json('new_values')->nullable(); // Valores nuevos
            $table->unsignedBigInteger('user_id')->nullable(); // Usuario que realizó la acción
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            // Índices para búsqueda rápida
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('user_id');
            $table->index('event');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');

        Schema::table('cargos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('sedes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('postulaciones', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('convocatorias', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('postulantes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
