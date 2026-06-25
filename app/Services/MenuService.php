<?php

namespace App\Services;

class MenuService
{
    public function all(): array
    {
        $opsRoles = ['superadmin', 'admin', 'registrasi'];
        $billRoles = ['superadmin', 'admin', 'kasir'];

        return [
            ['grup' => null, 'items' => [
                ['ico' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard',
                    'roles' => ['superadmin', 'admin', 'registrasi', 'dokter', 'farmasi', 'kasir']],
            ]],
            ['grup' => 'Rekam Medis', 'items' => [
                ['ico' => 'rekam', 'label' => 'Rekam Medis', 'legacy' => 'modules/rekam_medis/index.php',
                    'roles' => ['superadmin', 'dokter']],
            ]],
            ['grup' => 'Operasional', 'items' => [
                ['ico' => 'calendar', 'label' => 'Daftar Kunjungan', 'legacy' => 'modules/registrasi/index.php',
                    'roles' => $opsRoles, 'match' => 'registrasi/index'],
                ['ico' => 'registrasi', 'label' => 'Pendaftaran Baru', 'legacy' => 'modules/registrasi/daftar.php',
                    'roles' => $opsRoles, 'match' => 'registrasi/daftar'],
                ['ico' => 'users', 'label' => 'Data Pasien', 'legacy' => 'modules/registrasi/pasien.php',
                    'roles' => $opsRoles, 'match' => 'registrasi/pasien'],
                ['ico' => 'ticket', 'label' => 'Papan Antrian', 'legacy' => 'modules/registrasi/antrian.php',
                    'roles' => ['superadmin', 'registrasi'], 'match' => 'registrasi/antrian'],
                ['ico' => 'pelayanan', 'label' => 'Pelayanan', 'legacy' => 'modules/pelayanan/index.php',
                    'roles' => ['dokter', 'farmasi'],
                    'children' => [
                        ['label' => 'Antrian Pemeriksaan', 'legacy' => 'modules/pelayanan/index.php', 'roles' => ['dokter']],
                        ['label' => 'Antrian Farmasi', 'legacy' => 'modules/pelayanan/farmasi.php', 'roles' => ['farmasi']],
                    ]],
            ]],
            ['grup' => 'Keuangan', 'items' => [
                ['ico' => 'billing', 'label' => 'Billing', 'legacy' => 'modules/billing/index.php',
                    'roles' => $billRoles],
                ['ico' => 'keuangan', 'label' => 'Keuangan', 'legacy' => 'modules/keuangan/index.php',
                    'roles' => $billRoles],
            ]],
            ['grup' => 'Data & Stok', 'items' => [
                ['ico' => 'master', 'label' => 'Master Data', 'legacy' => 'modules/master/index.php',
                    'roles' => ['superadmin'],
                    'children' => [
                        ['label' => 'Layanan & Tarif', 'legacy' => 'modules/master/index.php?g=' . urlencode('Layanan & Tarif'), 'badge_group' => 'Layanan & Tarif'],
                        ['label' => 'SDM & Poli', 'legacy' => 'modules/master/index.php?g=' . urlencode('SDM & Poli'), 'badge_group' => 'SDM & Poli'],
                        ['label' => 'Farmasi', 'legacy' => 'modules/master/index.php?g=' . urlencode('Farmasi'), 'badge_group' => 'Farmasi'],
                        ['label' => 'Penjamin & Bank', 'legacy' => 'modules/master/index.php?g=' . urlencode('Penjamin & Bank'), 'badge_group' => 'Penjamin & Bank'],
                        ['label' => 'Pasien', 'legacy' => 'modules/master/index.php?g=' . urlencode('Pasien'), 'badge_group' => 'Pasien'],
                        ['label' => 'Kode Pembatalan', 'legacy' => 'modules/master/index.php?g=' . urlencode('Billing'), 'badge_group' => 'Billing'],
                    ]],
                ['ico' => 'inventory', 'label' => 'Inventory', 'legacy' => 'modules/inventory/index.php',
                    'roles' => ['superadmin', 'farmasi']],
            ]],
            ['grup' => 'Lainnya', 'items' => [
                ['ico' => 'laporan', 'label' => 'Laporan', 'legacy' => 'modules/laporan/index.php',
                    'roles' => ['superadmin', 'admin', 'registrasi', 'dokter', 'farmasi', 'kasir'],
                    'children' => [
                        ['label' => 'Operasional', 'legacy' => 'modules/laporan/index.php?g=' . urlencode('Operasional'),
                            'lap_group' => 'Operasional', 'roles' => ['registrasi', 'dokter', 'kasir', 'admin', 'superadmin']],
                        ['label' => 'Keuangan', 'legacy' => 'modules/laporan/index.php?g=' . urlencode('Keuangan'),
                            'lap_group' => 'Keuangan', 'roles' => ['kasir', 'admin', 'superadmin']],
                        ['label' => 'Penunjang', 'legacy' => 'modules/laporan/index.php?g=' . urlencode('Penunjang'),
                            'lap_group' => 'Penunjang', 'roles' => ['dokter', 'farmasi', 'superadmin']],
                    ]],
            ]],
            ['grup' => 'Pengaturan', 'items' => [
                ['ico' => 'hospital', 'label' => 'Profil Klinik', 'legacy' => 'modules/pengaturan/profil.php',
                    'roles' => ['superadmin']],
                ['ico' => 'user', 'label' => 'Pengguna & Role', 'legacy' => 'modules/pengaturan/users.php',
                    'roles' => ['superadmin']],
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
                return $role === 'superadmin' || in_array($role, $item['roles'], true);
            });

            if ($items === []) {
                continue;
            }

            $menu[] = ['grup' => $grup['grup'], 'items' => array_values($items)];
        }

        return $menu;
    }
}
