<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SimKlinikMasterSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('kelompok_pasien')->insert([
            ['nama' => 'Umum'],
            ['nama' => 'BPJS'],
            ['nama' => 'Asuransi'],
            ['nama' => 'Corporate'],
        ]);

        DB::table('poli')->insert([
            ['kode' => 'UMUM', 'nama' => 'Poli Umum', 'status' => 'aktif'],
            ['kode' => 'GIGI', 'nama' => 'Poli Gigi', 'status' => 'aktif'],
            ['kode' => 'KIA', 'nama' => 'Poli KIA', 'status' => 'aktif'],
            ['kode' => 'ANAK', 'nama' => 'Poli Anak', 'status' => 'aktif'],
        ]);

        DB::table('bank')->insert([
            [
                'nama_bank' => 'BCA',
                'no_rekening' => '7045368149',
                'atas_nama' => 'PT Sapta Genki Clinic',
                'cabang' => 'KCP Panata Yuda',
                'status' => 'aktif',
            ],
        ]);

        DB::table('spesialisasi')->insert([
            ['nama' => 'Dokter Umum'],
            ['nama' => 'Dokter Gigi'],
            ['nama' => 'Spesialis Anak'],
        ]);

        DB::table('dokter')->insert([
            ['kode' => 'GBKDR0001', 'nama' => 'dr. Andi Wijaya', 'spesialisasi_id' => 1, 'tarif_jasa' => 50000, 'status' => 'aktif'],
            ['kode' => 'GBKDR0002', 'nama' => 'drg. Siti Rahma', 'spesialisasi_id' => 2, 'tarif_jasa' => 75000, 'status' => 'aktif'],
            ['kode' => 'GBKDR0003', 'nama' => 'dr. Budi Santoso, Sp.A', 'spesialisasi_id' => 3, 'tarif_jasa' => 100000, 'status' => 'aktif'],
        ]);

        DB::table('tindakan')->insert([
            ['kode' => 'GBAD0001', 'nama' => 'Konsultasi Dokter Umum', 'kode_icd9' => null, 'tarif' => 25000, 'status' => 'aktif'],
            ['kode' => 'GBAD0002', 'nama' => 'Perawatan Luka', 'kode_icd9' => '86.59', 'tarif' => 50000, 'status' => 'aktif'],
            ['kode' => 'GBAD0003', 'nama' => 'Nebulizer', 'kode_icd9' => '93.94', 'tarif' => 75000, 'status' => 'aktif'],
            ['kode' => 'GBAD0004', 'nama' => 'Injeksi', 'kode_icd9' => '99.29', 'tarif' => 30000, 'status' => 'aktif'],
        ]);

        DB::table('lab_kategori')->insert([
            ['nama' => 'Hematologi'],
            ['nama' => 'Kimia Darah'],
            ['nama' => 'Urinalisa'],
        ]);

        DB::table('lab_pemeriksaan')->insert([
            ['kategori_id' => 1, 'kode' => 'GBLA0001', 'nama' => 'Hemoglobin (Hb)', 'satuan' => 'g/dL', 'nilai_rujukan' => '12-16', 'tarif' => 25000, 'status' => 'aktif'],
            ['kategori_id' => 1, 'kode' => 'GBLA0002', 'nama' => 'Leukosit', 'satuan' => '/uL', 'nilai_rujukan' => '4000-10000', 'tarif' => 25000, 'status' => 'aktif'],
            ['kategori_id' => 2, 'kode' => 'GBLA0003', 'nama' => 'Gula Darah Sewaktu', 'satuan' => 'mg/dL', 'nilai_rujukan' => '<200', 'tarif' => 30000, 'status' => 'aktif'],
            ['kategori_id' => 2, 'kode' => 'GBLA0004', 'nama' => 'Kolesterol Total', 'satuan' => 'mg/dL', 'nilai_rujukan' => '<200', 'tarif' => 40000, 'status' => 'aktif'],
            ['kategori_id' => 3, 'kode' => 'GBLA0005', 'nama' => 'Urin Lengkap', 'satuan' => null, 'nilai_rujukan' => 'Normal', 'tarif' => 35000, 'status' => 'aktif'],
        ]);

        DB::table('rad_kategori')->insert([
            ['nama' => 'Rontgen'],
            ['nama' => 'USG'],
        ]);

        DB::table('rad_pemeriksaan')->insert([
            ['kategori_id' => 1, 'kode' => 'GBRA0001', 'nama' => 'Thorax PA', 'tarif' => 100000, 'status' => 'aktif'],
            ['kategori_id' => 1, 'kode' => 'GBRA0002', 'nama' => 'Foto Ekstremitas', 'tarif' => 120000, 'status' => 'aktif'],
            ['kategori_id' => 2, 'kode' => 'GBRA0003', 'nama' => 'USG Abdomen', 'tarif' => 150000, 'status' => 'aktif'],
        ]);

        DB::table('obat_kategori')->insert([
            ['nama' => 'Analgesik'],
            ['nama' => 'Antibiotik'],
            ['nama' => 'Vitamin'],
        ]);

        DB::table('obat_satuan')->insert([
            ['nama' => 'Tablet'],
            ['nama' => 'Kapsul'],
            ['nama' => 'Botol'],
            ['nama' => 'Strip'],
        ]);

        DB::table('obat')->insert([
            ['kode' => 'GBFA0001', 'nama' => 'Paracetamol 500mg', 'kategori_id' => 1, 'satuan_id' => 1, 'harga_beli' => 300, 'harga_jual' => 500, 'stok' => 500, 'stok_minimal' => 50, 'status' => 'aktif'],
            ['kode' => 'GBFA0002', 'nama' => 'Amoxicillin 500mg', 'kategori_id' => 2, 'satuan_id' => 2, 'harga_beli' => 800, 'harga_jual' => 1200, 'stok' => 300, 'stok_minimal' => 40, 'status' => 'aktif'],
            ['kode' => 'GBFA0003', 'nama' => 'Vitamin C 50mg', 'kategori_id' => 3, 'satuan_id' => 1, 'harga_beli' => 200, 'harga_jual' => 400, 'stok' => 400, 'stok_minimal' => 50, 'status' => 'aktif'],
            ['kode' => 'GBFA0004', 'nama' => 'Antasida Syrup', 'kategori_id' => 1, 'satuan_id' => 3, 'harga_beli' => 5000, 'harga_jual' => 8000, 'stok' => 60, 'stok_minimal' => 10, 'status' => 'aktif'],
        ]);

        DB::table('setting')->insert([
            ['k' => 'clinic_name', 'v' => 'PT Sapta Genki Clinic'],
            ['k' => 'clinic_unit', 'v' => 'Unit Bayuharta — Karawang'],
            ['k' => 'clinic_address', 'v' => 'Karawang, Jawa Barat'],
        ]);
    }
}
