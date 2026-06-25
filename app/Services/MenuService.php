<?php

namespace App\Services;

class MenuService
{
    public function all(): array
    {
        return [
            ['grup' => null, 'items' => [
                ['ico' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard',
                    'roles' => ['admin', 'registrasi', 'dokter', 'farmasi', 'kasir']],
            ]],
            ['grup' => 'Operasional', 'items' => [
                ['ico' => 'registrasi', 'label' => 'Registrasi', 'legacy' => 'modules/registrasi/index.php',
                    'roles' => ['admin', 'registrasi'],
                    'children' => [
                        ['label' => 'Daftar Kunjungan', 'legacy' => 'modules/registrasi/index.php'],
                        ['label' => 'Pendaftaran Baru', 'legacy' => 'modules/registrasi/daftar.php'],
                        ['label' => 'Data Pasien', 'legacy' => 'modules/registrasi/pasien.php'],
                        ['label' => 'Papan Antrian', 'legacy' => 'modules/registrasi/antrian.php'],
                    ]],
                ['ico' => 'pelayanan', 'label' => 'Pelayanan', 'legacy' => 'modules/pelayanan/index.php',
                    'roles' => ['admin', 'dokter', 'farmasi'],
                    'children' => [
                        ['label' => 'Antrian Pemeriksaan', 'legacy' => 'modules/pelayanan/index.php', 'roles' => ['admin', 'dokter']],
                        ['label' => 'Antrian Farmasi', 'legacy' => 'modules/pelayanan/farmasi.php', 'roles' => ['admin', 'farmasi']],
                    ]],
                ['ico' => 'rekam', 'label' => 'Rekam Medis', 'legacy' => 'modules/rekam_medis/index.php',
                    'roles' => ['admin', 'dokter']],
            ]],
            ['grup' => 'Keuangan', 'items' => [
                ['ico' => 'billing', 'label' => 'Billing', 'legacy' => 'modules/billing/index.php',
                    'roles' => ['admin', 'kasir']],
                ['ico' => 'keuangan', 'label' => 'Keuangan', 'legacy' => 'modules/keuangan/index.php',
                    'roles' => ['admin', 'kasir']],
            ]],
            ['grup' => 'Data & Stok', 'items' => [
                ['ico' => 'master', 'label' => 'Master Data', 'legacy' => 'modules/master/index.php',
                    'roles' => ['admin']],
                ['ico' => 'inventory', 'label' => 'Inventory', 'legacy' => 'modules/inventory/index.php',
                    'roles' => ['admin', 'farmasi']],
            ]],
            ['grup' => 'Lainnya', 'items' => [
                ['ico' => 'laporan', 'label' => 'Laporan', 'legacy' => 'modules/laporan/index.php',
                    'roles' => ['admin', 'registrasi', 'dokter', 'farmasi', 'kasir']],
            ]],
            ['grup' => 'Pengaturan', 'items' => [
                ['ico' => 'hospital', 'label' => 'Profil Klinik', 'legacy' => 'modules/pengaturan/profil.php',
                    'roles' => ['admin']],
                ['ico' => 'user', 'label' => 'Pengguna & Role', 'legacy' => 'modules/pengaturan/users.php',
                    'roles' => ['admin']],
            ]],
        ];
    }

    public function urlFor(array $item): string
    {
        if (! empty($item['route'])) {
            return route($item['route']);
        }

        if (! empty($item['legacy'])) {
            return route('legacy', ['path' => $item['legacy']]);
        }

        return '#';
    }

    public function forRole(?string $role): array
    {
        $menu = [];

        foreach ($this->all() as $grup) {
            $items = array_filter($grup['items'], function ($item) use ($role) {
                return $role === 'admin' || in_array($role, $item['roles'], true);
            });

            if ($items === []) {
                continue;
            }

            $menu[] = ['grup' => $grup['grup'], 'items' => array_values($items)];
        }

        return $menu;
    }
}
