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
        Schema::table('sedes', function (Blueprint $table) {
            if (!Schema::hasColumn('sedes', 'direccion')) {
                $table->string('direccion', 500)->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('sedes', 'ciudad')) {
                $table->string('ciudad', 100)->nullable()->after('direccion');
            }
        });

        Schema::table('cargos', function (Blueprint $table) {
            if (!Schema::hasColumn('cargos', 'descripcion')) {
                $table->text('descripcion')->nullable()->after('nombre');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sedes', function (Blueprint $table) {
            $table->dropColumn(['direccion', 'ciudad']);
        });

        Schema::table('cargos', function (Blueprint $table) {
            $table->dropColumn(['descripcion']);
        });
    }
};
