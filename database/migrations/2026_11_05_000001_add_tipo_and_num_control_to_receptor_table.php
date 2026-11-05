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
        if (Schema::hasTable('receptor')) {
            Schema::table('receptor', function (Blueprint $table) {
                if (! Schema::hasColumn('receptor', 'tipo')) {
                    $table->enum('tipo', ['Docente', 'Administrativo', 'Auxiliar', 'Alumno', 'Externo'])
                        ->nullable()
                        ->after('apellido_materno');
                }
                if (! Schema::hasColumn('receptor', 'num_control')) {
                    $table->string('num_control', 30)
                        ->nullable()
                        ->after('tipo');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('receptor')) {
            Schema::table('receptor', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('receptor', 'tipo')) {
                    $cols[] = 'tipo';
                }
                if (Schema::hasColumn('receptor', 'num_control')) {
                    $cols[] = 'num_control';
                }
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};

