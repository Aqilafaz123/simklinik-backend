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
        Schema::table('pasien', function (Blueprint $table) {
            $table->text('alamat')->nullable()->after('pekerjaan');
            $table->string('kode_pos', 20)->nullable()->after('alamat');
            $table->string('kelurahan', 100)->nullable()->after('kode_pos');
            $table->string('kecamatan', 100)->nullable()->after('kelurahan');
            $table->string('kota', 100)->nullable()->after('kecamatan');
            $table->string('provinsi', 100)->nullable()->after('kota');
            $table->string('telepon', 30)->nullable()->after('provinsi');
            $table->string('email', 100)->nullable()->after('telepon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pasien', function (Blueprint $table) {
            $table->dropColumn([
                'alamat', 'kode_pos', 'kelurahan', 'kecamatan', 
                'kota', 'provinsi', 'telepon', 'email'
            ]);
        });
    }
};
