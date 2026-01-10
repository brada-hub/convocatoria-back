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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombres')->after('id');
            $table->string('apellidos')->after('nombres');
            $table->string('ci')->unique()->after('apellidos');
            $table->boolean('activo')->default(true)->after('password');
            $table->boolean('must_change_password')->default(true)->after('activo');

            // Reconfigure fields
            $table->string('email')->nullable()->change();
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name');
            $table->string('email')->nullable(false)->change();

            $table->dropColumn(['nombres', 'apellidos', 'ci', 'activo', 'must_change_password']);
        });
    }
};
