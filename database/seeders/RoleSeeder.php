<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            ['kode' => 'admin', 'nama' => 'Administrator', 'keterangan' => 'Akses penuh seluruh sistem'],
            ['kode' => 'registrasi', 'nama' => 'Petugas Registrasi', 'keterangan' => 'Pendaftaran pasien & antrian'],
            ['kode' => 'dokter', 'nama' => 'Dokter', 'keterangan' => 'Pemeriksaan & rekam medis'],
            ['kode' => 'farmasi', 'nama' => 'Petugas Farmasi', 'keterangan' => 'Resep, obat & inventory'],
            ['kode' => 'kasir', 'nama' => 'Kasir', 'keterangan' => 'Billing, invoice & pembayaran'],
        ]);
    }
}
