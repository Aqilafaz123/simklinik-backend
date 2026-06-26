<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pasien MODIFY COLUMN gol_darah ENUM('A','A+','A-','B','B+','B-','AB','AB+','AB-','O','O+','O-','-') DEFAULT '-'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pasien MODIFY COLUMN gol_darah ENUM('A','B','AB','O','A+','A-','B+','B-','AB+','AB-','O+','O-','-') DEFAULT '-'");
    }
};
