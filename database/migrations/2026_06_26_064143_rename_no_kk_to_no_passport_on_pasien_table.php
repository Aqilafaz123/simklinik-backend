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
        if (Schema::hasColumn('pasien', 'no_kk')) {
            Schema::table('pasien', function (Blueprint $table) {
                $table->renameColumn('no_kk', 'no_passport');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('pasien', 'no_passport')) {
            Schema::table('pasien', function (Blueprint $table) {
                $table->renameColumn('no_passport', 'no_kk');
            });
        }
    }
};
