<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            // Perfil profesional requerido
            $table->text('perfil_profesional')->nullable()->after('descripcion');

            // Experiencia requerida
            $table->text('experiencia_requerida')->nullable()->after('perfil_profesional');

            // Hora límite de postulación (se combina con fecha_cierre)
            $table->time('hora_limite')->nullable()->default('23:59:00')->after('fecha_cierre');

            // Imagen de afiche generado
            $table->string('afiche_imagen')->nullable()->after('estado');
        });

        // Agregar soft deletes si no existe
        if (!Schema::hasColumn('convocatorias', 'deleted_at')) {
            Schema::table('convocatorias', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->dropColumn(['perfil_profesional', 'experiencia_requerida', 'hora_limite', 'afiche_imagen']);
        });
    }
};
