<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->string('estado')->default('activa')->change();
        });

        // Actualizar las existentes que no sean borrador
        \DB::table('convocatorias')->where('estado', 'borrador')->update(['estado' => 'activa']);
    }

    public function down(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->string('estado')->default('borrador')->change();
        });
    }
};
