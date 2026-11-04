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
            $table->string('codigo', 50)->nullable()->unique()->after('num_serie');
            $table->string('estacion', 20)->nullable()->index()->after('codigo');
            $table->enum('tipo_impresora', ['Láser', 'Inyección'])->nullable()->after('estacion');
            $table->string('sistema_operativo', 100)->nullable()->after('tipo_impresora');
            $table->string('modelo', 100)->nullable()->after('sistema_operativo');
            $table->string('especificacion_texto', 150)->nullable()->after('modelo');
            $table->foreignId('departamento_id')->nullable()->after('especificacion_texto')->constrained('departamento', 'id_departamento')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->after('departamento_id')->constrained('area', 'id_area')->nullOnDelete();
            $table->foreignId('salon_id')->nullable()->after('area_id')->constrained('salones', 'id')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario', function (Blueprint $table) {
            $table->dropForeign(['departamento_id']);
            $table->dropForeign(['area_id']);
            $table->dropForeign(['salon_id']);
            $table->dropIndex(['estacion']);
            $table->dropUnique(['codigo']);
            $table->dropColumn([
                'codigo',
                'estacion',
                'tipo_impresora',
                'sistema_operativo',
                'modelo',
                'especificacion_texto',
                'departamento_id',
                'area_id',
                'salon_id',
            ]);
        });
    }
};

