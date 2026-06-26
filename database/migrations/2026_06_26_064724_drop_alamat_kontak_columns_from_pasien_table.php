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
        if (Schema::hasColumn('pasien', 'alamat')) {
            Schema::table('pasien', function (Blueprint $table) {
                $table->dropColumn([
                    'alamat', 'rt_rw', 'kode_pos', 'kelurahan', 'kecamatan', 
                    'kota', 'provinsi', 'telepon', 'email'
                ]);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('pasien', 'alamat')) {
            Schema::table('pasien', function (Blueprint $table) {
                $table->text('alamat')->nullable();
                $table->string('rt_rw', 15)->nullable();
                $table->string('kelurahan', 60)->nullable();
                $table->string('kecamatan', 60)->nullable();
                $table->string('kota', 60)->nullable();
                $table->string('provinsi', 60)->nullable();
                $table->string('kode_pos', 10)->nullable();
                $table->string('telepon', 20)->nullable();
                $table->string('email', 80)->nullable();
            });
        }
    }
};
