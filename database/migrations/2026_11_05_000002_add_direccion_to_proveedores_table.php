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
        if (Schema::hasTable('proveedores')) {
            Schema::table('proveedores', function (Blueprint $table) {
                if (! Schema::hasColumn('proveedores', 'direccion')) {
                    $table->string('direccion', 255)
                        ->nullable()
                        ->after('rfc');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('proveedores')) {
            Schema::table('proveedores', function (Blueprint $table) {
                if (Schema::hasColumn('proveedores', 'direccion')) {
                    $table->dropColumn('direccion');
                }
            });
        }
    }
};

