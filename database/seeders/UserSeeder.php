<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Password default semua akun: admin123 (ganti setelah login!)
        $password = '$2y$10$JuPmIcHsfjyxZtqvSvPYtOKH3YCy2zQ4w6OQyl4Sp.c5KjWt//0iy';
        $now = now();

        $roles = DB::table('roles')->pluck('id', 'kode');
        $poliUmumId = DB::table('poli')->where('kode', 'UMUM')->value('id');

        $users = [
            [
                'role_id' => $roles['admin'],
                'poli_id' => null,
                'nama' => 'Administrator',
                'username' => 'admin',
            ],
            [
                'role_id' => $roles['registrasi'],
                'poli_id' => null,
                'nama' => 'Petugas Registrasi',
                'username' => 'registrasi',
            ],
            [
                'role_id' => $roles['dokter'],
                'poli_id' => $poliUmumId,
                'nama' => 'dr. Andi Wijaya',
                'username' => 'dokter',
            ],
            [
                'role_id' => $roles['farmasi'],
                'poli_id' => null,
                'nama' => 'Petugas Farmasi',
                'username' => 'farmasi',
            ],
            [
                'role_id' => $roles['kasir'],
                'poli_id' => null,
                'nama' => 'Petugas Kasir',
                'username' => 'kasir',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['username' => $user['username']],
                [
                    'role_id' => $user['role_id'],
                    'poli_id' => $user['poli_id'],
                    'nama' => $user['nama'],
                    'password' => $password,
                    'status' => 'aktif',
                    'created_at' => $now,
                ]
            );
        }

        $dokterUserId = DB::table('users')->where('username', 'dokter')->value('id');
        if ($dokterUserId) {
            DB::table('dokter')
                ->where('kode', 'GBKDR0001')
                ->update(['user_id' => $dokterUserId, 'poli_id' => $poliUmumId]);
        }
    }
}
