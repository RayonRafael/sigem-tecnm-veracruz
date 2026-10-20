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
        Schema::table('departamento', function (Blueprint $table) {
            $table->foreignId('edificio_id')
                ->nullable()
                ->after('id_departamento')
                ->constrained('edificios')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departamento', function (Blueprint $table) {
            $table->dropForeign(['edificio_id']);
            $table->dropColumn('edificio_id');
        });
    }
};
