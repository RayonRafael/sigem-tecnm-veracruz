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
        Schema::table('inventario', function (Blueprint $table) {
            if (! Schema::hasColumn('inventario', 'estado_previo_cambio')) {
                $table->string('estado_previo_cambio', 50)->nullable();
            }
            if (! Schema::hasColumn('inventario', 'ubicacion_solicitada')) {
                $table->json('ubicacion_solicitada')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario', function (Blueprint $table) {
            if (Schema::hasColumn('inventario', 'estado_previo_cambio')) {
                $table->dropColumn('estado_previo_cambio');
            }
            if (Schema::hasColumn('inventario', 'ubicacion_solicitada')) {
                $table->dropColumn('ubicacion_solicitada');
            }
        });
    }
};

