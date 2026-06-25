<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['kode' => 'superadmin', 'nama' => 'Super Administrator', 'keterangan' => 'Akses penuh: rekam medis, master data, inventory & pengaturan'],
            ['kode' => 'admin', 'nama' => 'Admin', 'keterangan' => 'Data pasien, pendaftaran, billing, keuangan & laporan'],
            ['kode' => 'registrasi', 'nama' => 'Petugas Registrasi', 'keterangan' => 'Pendaftaran pasien & antrian'],
            ['kode' => 'dokter', 'nama' => 'Dokter', 'keterangan' => 'Pemeriksaan & rekam medis'],
            ['kode' => 'farmasi', 'nama' => 'Petugas Farmasi', 'keterangan' => 'Resep, obat & inventory'],
            ['kode' => 'kasir', 'nama' => 'Kasir', 'keterangan' => 'Billing, invoice & pembayaran'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(['kode' => $role['kode']], $role);
        }
    }
}
