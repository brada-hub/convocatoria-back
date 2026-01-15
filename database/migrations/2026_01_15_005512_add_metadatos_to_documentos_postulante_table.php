<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_postulante', function (Blueprint $table) {
            if (!Schema::hasColumn('documentos_postulante', 'metadatos')) {
                $table->json('metadatos')->after('archivo_pdf')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('documentos_postulante', function (Blueprint $table) {
            $table->dropColumn('metadatos');
        });
    }
};
