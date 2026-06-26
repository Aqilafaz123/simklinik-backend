<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pasien MODIFY COLUMN gol_darah ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-','-') DEFAULT '-'");
    }

    public function down(): void
    {
        // Update any specific rhesus values back to general types before reverting the ENUM
        DB::table('pasien')->whereIn('gol_darah', ['A+','A-'])->update(['gol_darah' => 'A']);
        DB::table('pasien')->whereIn('gol_darah', ['B+','B-'])->update(['gol_darah' => 'B']);
        DB::table('pasien')->whereIn('gol_darah', ['AB+','AB-'])->update(['gol_darah' => 'AB']);
        DB::table('pasien')->whereIn('gol_darah', ['O+','O-'])->update(['gol_darah' => 'O']);
        
        DB::statement("ALTER TABLE pasien MODIFY COLUMN gol_darah ENUM('A','B','AB','O','-') DEFAULT '-'");
    }
};
