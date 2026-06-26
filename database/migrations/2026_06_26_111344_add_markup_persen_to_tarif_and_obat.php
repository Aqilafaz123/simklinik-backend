<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE lab_pemeriksaan ADD markup_persen DECIMAL(5,2) DEFAULT 0.00 AFTER tarif");
        DB::statement("ALTER TABLE rad_pemeriksaan ADD markup_persen DECIMAL(5,2) DEFAULT 0.00 AFTER tarif");
        DB::statement("ALTER TABLE obat DROP COLUMN harga_jual, ADD markup_persen DECIMAL(5,2) DEFAULT 0.00 AFTER harga_beli");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE obat DROP COLUMN markup_persen, ADD harga_jual DECIMAL(12,2) DEFAULT 0.00 AFTER harga_beli");
        DB::statement("ALTER TABLE rad_pemeriksaan DROP COLUMN markup_persen");
        DB::statement("ALTER TABLE lab_pemeriksaan DROP COLUMN markup_persen");
    }
};
