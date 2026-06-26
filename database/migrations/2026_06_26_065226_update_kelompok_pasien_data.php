<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('kelompok_pasien')->where('nama', 'Umum')->update(['nama' => 'Umum/Cash']);
        DB::table('kelompok_pasien')->where('nama', 'Corporate')->update(['nama' => 'Perusahaan']);
        
        $bpjs = DB::table('kelompok_pasien')->where('nama', 'BPJS')->first();
        if ($bpjs) {
            $used = DB::table('pasien')->where('kelompok_id', $bpjs->id)->exists();
            if (!$used) {
                DB::table('kelompok_pasien')->where('id', $bpjs->id)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('kelompok_pasien')->where('nama', 'Umum/Cash')->update(['nama' => 'Umum']);
        DB::table('kelompok_pasien')->where('nama', 'Perusahaan')->update(['nama' => 'Corporate']);
        
        $bpjsExists = DB::table('kelompok_pasien')->where('nama', 'BPJS')->exists();
        if (!$bpjsExists) {
            DB::table('kelompok_pasien')->insert(['nama' => 'BPJS']);
        }
    }
};
