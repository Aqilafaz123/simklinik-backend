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
                ['ico' => 'dashboard', 'label' => __('menu.dashboard'), 'route' => 'dashboard',
                    'roles' => ['superadmin', 'admin', 'registrasi', 'dokter', 'farmasi', 'kasir']],
            ]],
            ['grup' => __('menu.groups.rekam_medis'), 'items' => [
                ['ico' => 'rekam', 'label' => __('menu.rekam_medis'), 'legacy' => 'modules/rekam_medis/index.php',
                    'roles' => ['superadmin', 'dokter']],
            ]],
            ['grup' => __('menu.groups.operasional'), 'items' => [
                ['ico' => 'calendar', 'label' => __('menu.visit_list'), 'legacy' => 'modules/registrasi/index.php',
                    'roles' => $opsRoles, 'match' => 'registrasi/index'],
                ['ico' => 'registrasi', 'label' => __('menu.new_registration'), 'legacy' => 'modules/registrasi/daftar.php',
                    'roles' => $opsRoles, 'match' => 'registrasi/daftar'],
                ['ico' => 'users', 'label' => __('menu.patient_data'), 'legacy' => 'modules/registrasi/pasien.php',
                    'roles' => $opsRoles, 'match' => 'registrasi/pasien'],
                ['ico' => 'ticket', 'label' => __('menu.queue_board'), 'legacy' => 'modules/registrasi/antrian.php',
                    'roles' => ['superadmin', 'registrasi'], 'match' => 'registrasi/antrian'],
                ['ico' => 'pelayanan', 'label' => __('menu.service'), 'legacy' => 'modules/pelayanan/index.php',
                    'roles' => ['dokter', 'farmasi'],
                    'children' => [
                        ['label' => __('menu.exam_queue'), 'legacy' => 'modules/pelayanan/index.php', 'roles' => ['dokter']],
                        ['label' => __('menu.pharmacy_queue'), 'legacy' => 'modules/pelayanan/farmasi.php', 'roles' => ['farmasi']],
                    ]],
            ]],
            ['grup' => __('menu.groups.keuangan'), 'items' => [
                ['ico' => 'billing', 'label' => __('menu.billing'), 'legacy' => 'modules/billing/index.php',
                    'roles' => $billRoles],
                ['ico' => 'keuangan', 'label' => __('menu.finance'), 'legacy' => 'modules/keuangan/index.php',
                    'roles' => $billRoles],
            ]],
            ['grup' => __('menu.groups.data_stok'), 'items' => [
                ['ico' => 'master', 'label' => __('menu.master_data'), 'legacy' => 'modules/master/index.php',
                    'roles' => ['superadmin'],
                    'children' => [
                        ['label' => __('menu.services_tariff'), 'legacy' => 'modules/master/index.php?g=' . urlencode('Layanan & Tarif'), 'badge_group' => 'Layanan & Tarif'],
                        ['label' => __('menu.staff_poli'), 'legacy' => 'modules/master/index.php?g=' . urlencode('SDM & Poli'), 'badge_group' => 'SDM & Poli'],
                        ['label' => __('menu.pharmacy'), 'legacy' => 'modules/master/index.php?g=' . urlencode('Farmasi'), 'badge_group' => 'Farmasi'],
                        ['label' => __('menu.insurance_bank'), 'legacy' => 'modules/master/index.php?g=' . urlencode('Penjamin & Bank'), 'badge_group' => 'Penjamin & Bank'],
                        ['label' => __('menu.patients'), 'legacy' => 'modules/master/index.php?g=' . urlencode('Pasien'), 'badge_group' => 'Pasien'],
                        ['label' => __('menu.cancel_codes'), 'legacy' => 'modules/master/index.php?g=' . urlencode('Billing'), 'badge_group' => 'Billing'],
                    ]],
                ['ico' => 'inventory', 'label' => __('menu.inventory'), 'legacy' => 'modules/inventory/index.php',
                    'roles' => ['superadmin', 'farmasi']],
            ]],
            ['grup' => __('menu.groups.lainnya'), 'items' => [
                ['ico' => 'laporan', 'label' => __('menu.reports'), 'legacy' => 'modules/laporan/index.php',
                    'roles' => ['superadmin', 'admin', 'registrasi', 'dokter', 'farmasi', 'kasir'],
                    'children' => [
                        ['label' => __('menu.operational'), 'legacy' => 'modules/laporan/index.php?g=' . urlencode('Operasional'),
                            'lap_group' => 'Operasional', 'roles' => ['registrasi', 'dokter', 'kasir', 'admin', 'superadmin']],
                        ['label' => __('menu.financial'), 'legacy' => 'modules/laporan/index.php?g=' . urlencode('Keuangan'),
                            'lap_group' => 'Keuangan', 'roles' => ['kasir', 'admin', 'superadmin']],
                        ['label' => __('menu.support'), 'legacy' => 'modules/laporan/index.php?g=' . urlencode('Penunjang'),
                            'lap_group' => 'Penunjang', 'roles' => ['dokter', 'farmasi', 'superadmin']],
                    ]],
            ]],
            ['grup' => __('menu.groups.pengaturan'), 'items' => [
                ['ico' => 'hospital', 'label' => __('menu.clinic_profile'), 'legacy' => 'modules/pengaturan/profil.php',
                    'roles' => ['superadmin']],
                ['ico' => 'user', 'label' => __('menu.users_roles'), 'legacy' => 'modules/pengaturan/users.php',
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
