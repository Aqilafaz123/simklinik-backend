<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pasien MODIFY COLUMN gol_darah ENUM('A','B','AB','O','A+','A-','B+','B-','AB+','AB-','O+','O-','-') DEFAULT '-'");
    }

    public function down(): void
    {
        // Reverting this means dropping the general variants if we wanted to be strict,
        // but it's safer to just fall back to the previous rhesus-only state
        DB::statement("ALTER TABLE pasien MODIFY COLUMN gol_darah ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-','-') DEFAULT '-'");
    }
};
