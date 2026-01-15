<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_postulante', function (Blueprint $table) {
            // MySQL needs an index for the foreign key.
            // Since post_doc_unique was being used for postulante_id,
            // we create a regular index first.
            $table->index('postulante_id');
            $table->dropUnique('post_doc_unique');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_postulante', function (Blueprint $table) {
             $table->unique(['postulante_id', 'tipo_documento_id'], 'post_doc_unique');
        });
    }
};
