<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE kunjungan MODIFY COLUMN jenis_penjamin ENUM('umum','bpjs','asuransi','corporate','ar') NOT NULL DEFAULT 'umum'");
    }

    public function down(): void
    {
        // First convert any 'ar' records to 'umum' so changing the ENUM doesn't crash
        DB::table('kunjungan')->where('jenis_penjamin', 'ar')->update(['jenis_penjamin' => 'umum']);
        
        DB::statement("ALTER TABLE kunjungan MODIFY COLUMN jenis_penjamin ENUM('umum','bpjs','asuransi','corporate') NOT NULL DEFAULT 'umum'");
    }
};
