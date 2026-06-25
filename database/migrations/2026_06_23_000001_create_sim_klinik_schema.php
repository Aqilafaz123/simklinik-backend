<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        $path = database_path('schema/sim_klinik.sql');
        $sql = file_get_contents($path);

        foreach ($this->splitStatements($sql) as $statement) {
            DB::unprepared($statement);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            'pembayaran', 'invoice', 'billing_detail', 'billing',
            'resep_detail', 'resep',
            'fisio_order_detail', 'fisio_order', 'fisio_pemeriksaan', 'fisio_kategori',
            'diag_order_detail', 'diag_order', 'diag_pemeriksaan', 'diag_kategori',
            'rad_order_detail', 'rad_order',
            'lab_order_detail', 'lab_order',
            'rm_tindakan', 'rm_diagnosa', 'rekam_medis', 'kunjungan',
            'stok_mutasi', 'pembelian_detail', 'pembelian', 'obat_batch',
            'jadwal_dokter', 'dokter', 'users', 'poli', 'spesialisasi',
            'tindakan', 'lab_pemeriksaan', 'lab_kategori',
            'rad_pemeriksaan', 'rad_kategori',
            'obat', 'obat_kategori', 'obat_satuan',
            'bank', 'asuransi', 'corporate', 'supplier',
            'pasien', 'kelompok_pasien', 'kode_pembatalan', 'setting', 'roles',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    /** @return list<string> */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';

        foreach (preg_split('/\R/', $sql) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }

            $buffer .= $line . "\n";

            if (str_ends_with(trim($line), ';')) {
                $statements[] = trim($buffer);
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $statements[] = trim($buffer);
        }

        return $statements;
    }
};
