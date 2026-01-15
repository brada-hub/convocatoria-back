<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_documento', function (Blueprint $table) {
            // Agregar slug si no existe
            if (!Schema::hasColumn('tipos_documento', 'slug')) {
                $table->string('slug')->nullable()->after('nombre');
            }

            // Campos dinámicos del formulario (JSON)
            if (!Schema::hasColumn('tipos_documento', 'campos')) {
                $table->json('campos')->nullable()->after('descripcion');
            }

            // Categoría
            if (!Schema::hasColumn('tipos_documento', 'categoria')) {
                $table->string('categoria')->default('general')->after('campos');
            }

            // Permite múltiples registros
            if (!Schema::hasColumn('tipos_documento', 'permite_multiples')) {
                $table->boolean('permite_multiples')->default(true)->after('categoria');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tipos_documento', function (Blueprint $table) {
            $columns = ['slug', 'campos', 'categoria', 'permite_multiples'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('tipos_documento', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
