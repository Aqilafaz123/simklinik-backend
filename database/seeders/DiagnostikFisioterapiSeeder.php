<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiagnostikFisioterapiSeeder extends Seeder
{
    public function run(): void
    {
        $diagKardio = $this->kategoriId('diag_kategori', 'Kardiologi');
        $diagRespi = $this->kategoriId('diag_kategori', 'Respirasi');
        $diagNeuro = $this->kategoriId('diag_kategori', 'Neurologi');

        $this->pemeriksaan('diag_pemeriksaan', 'GBDG0001', $diagKardio, 'EKG (Elektrokardiogram)', 150000);
        $this->pemeriksaan('diag_pemeriksaan', 'GBDG0002', $diagKardio, 'Treadmill Test', 450000);
        $this->pemeriksaan('diag_pemeriksaan', 'GBDG0003', $diagRespi, 'Spirometri', 200000);

        $fisioElektro = $this->kategoriId('fisio_kategori', 'Elektroterapi');
        $fisioLatihan = $this->kategoriId('fisio_kategori', 'Terapi Latihan');

        $this->pemeriksaan('fisio_pemeriksaan', 'GBFS0001', $fisioElektro, 'Infra Red (IR)', 75000);
        $this->pemeriksaan('fisio_pemeriksaan', 'GBFS0002', $fisioElektro, 'TENS', 100000);
        $this->pemeriksaan('fisio_pemeriksaan', 'GBFS0003', $fisioLatihan, 'Terapi Latihan (Exercise)', 120000);
    }

    private function kategoriId(string $table, string $nama): int
    {
        $existing = DB::table($table)->where('nama', $nama)->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table($table)->insertGetId(['nama' => $nama]);
    }

    private function pemeriksaan(string $table, string $kode, int $kategoriId, string $nama, float $tarif): void
    {
        if (DB::table($table)->where('nama', $nama)->exists()) {
            return;
        }

        DB::table($table)->insert([
            'kategori_id' => $kategoriId,
            'kode' => $kode,
            'nama' => $nama,
            'tarif' => $tarif,
            'status' => 'aktif',
        ]);
    }
}
