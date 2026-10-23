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
                if (!Schema::hasColumn('receptor', 'cargo')) {
                    $table->string('cargo', 100)->nullable()->after('apellido_materno');
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
                if (Schema::hasColumn('receptor', 'cargo')) {
                    $table->dropColumn('cargo');
                }
            });
        }
    }
};
