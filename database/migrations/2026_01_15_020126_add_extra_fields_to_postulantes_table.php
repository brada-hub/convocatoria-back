<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('postulantes', function (Blueprint $table) {
            $table->string('ci_expedido', 5)->nullable()->after('ci');
            $table->string('nacionalidad')->nullable()->after('genero');
            $table->string('direccion')->nullable()->after('nacionalidad');
            $table->string('carta_postulacion_pdf')->nullable()->after('foto_perfil');
            $table->string('curriculum_vitae_pdf')->nullable()->after('carta_postulacion_pdf');
            $table->string('ci_documento_pdf')->nullable()->after('curriculum_vitae_pdf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('postulantes', function (Blueprint $table) {
            $table->dropColumn([
                'ci_expedido',
                'nacionalidad',
                'direccion',
                'carta_postulacion_pdf',
                'curriculum_vitae_pdf',
                'ci_documento_pdf'
            ]);
        });
    }
};
