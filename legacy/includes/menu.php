<?php
/**
 * Definisi menu aplikasi (struktur menu sesuai blueprint) + hak akses per role.
 * 'roles' = daftar role yang boleh melihat menu. Superadmin selalu melihat semua.
 */
require_once __DIR__ . '/lang.php';

function get_menu(): array
{
    $opsRoles = ['superadmin', 'admin', 'registrasi'];
    $billRoles = ['superadmin', 'admin', 'kasir'];

    return [
        ['grup' => null, 'items' => [
            ['ico' => 'dashboard', 'label' => t('menu.dashboard'), 'url' => 'modules/dashboard/index.php',
             'roles' => ['superadmin', 'admin', 'registrasi', 'dokter', 'farmasi', 'kasir']],
        ]],
        ['grup' => t('menu.groups.rekam_medis'), 'items' => [
            ['ico' => 'rekam', 'label' => t('menu.rekam_medis'), 'url' => 'modules/rekam_medis/index.php',
             'roles' => ['superadmin', 'dokter']],
        ]],
        ['grup' => t('menu.groups.operasional'), 'items' => [
            ['ico' => 'calendar', 'label' => t('menu.visit_list'), 'url' => 'modules/registrasi/index.php',
             'roles' => $opsRoles, 'match' => 'registrasi/index'],
            ['ico' => 'registrasi', 'label' => t('menu.new_registration'), 'url' => 'modules/registrasi/daftar.php',
             'roles' => $opsRoles, 'match' => 'registrasi/daftar'],
            ['ico' => 'users', 'label' => t('menu.patient_data'), 'url' => 'modules/registrasi/pasien.php',
             'roles' => $opsRoles, 'match' => 'registrasi/pasien'],
            ['ico' => 'ticket', 'label' => t('menu.queue_board'), 'url' => 'modules/registrasi/antrian.php',
             'roles' => ['superadmin', 'registrasi'], 'match' => 'registrasi/antrian'],
            ['ico' => 'pelayanan', 'label' => t('menu.service'), 'url' => 'modules/pelayanan/index.php',
             'roles' => ['dokter', 'farmasi'],
             'children' => [
                 ['label' => t('menu.exam_queue'), 'url' => 'modules/pelayanan/index.php', 'roles' => ['dokter']],
                 ['label' => t('menu.pharmacy_queue'), 'url' => 'modules/pelayanan/farmasi.php', 'roles' => ['farmasi']],
             ]],
        ]],
        ['grup' => t('menu.groups.keuangan'), 'items' => [
            ['ico' => 'billing', 'label' => t('menu.billing'), 'url' => 'modules/billing/index.php',
             'roles' => $billRoles],
            ['ico' => 'keuangan', 'label' => t('menu.finance'), 'url' => 'modules/keuangan/index.php',
             'roles' => $billRoles],
        ]],
        ['grup' => t('menu.groups.data_stok'), 'items' => [
            ['ico' => 'master', 'label' => t('menu.master_data'), 'url' => 'modules/master/index.php',
             'roles' => ['superadmin'],
             'children' => [
                 ['label' => t('menu.services_tariff'), 'url' => 'modules/master/index.php?g=' . urlencode('Layanan & Tarif'), 'badge_group' => 'Layanan & Tarif'],
                 ['label' => t('menu.staff_poli'), 'url' => 'modules/master/index.php?g=' . urlencode('SDM & Poli'), 'badge_group' => 'SDM & Poli'],
                 ['label' => t('menu.pharmacy'), 'url' => 'modules/master/index.php?g=' . urlencode('Farmasi'), 'badge_group' => 'Farmasi'],
                 ['label' => t('menu.insurance_bank'), 'url' => 'modules/master/index.php?g=' . urlencode('Penjamin & Bank'), 'badge_group' => 'Penjamin & Bank'],
                 ['label' => t('menu.patients'), 'url' => 'modules/master/index.php?g=' . urlencode('Pasien'), 'badge_group' => 'Pasien'],
                 ['label' => t('menu.cancel_codes'), 'url' => 'modules/master/index.php?g=' . urlencode('Billing'), 'badge_group' => 'Billing'],
             ]],
            ['ico' => 'inventory', 'label' => t('menu.inventory'), 'url' => 'modules/inventory/index.php',
             'roles' => ['superadmin', 'farmasi']],
        ]],
        ['grup' => t('menu.groups.lainnya'), 'items' => [
            ['ico' => 'laporan', 'label' => t('menu.reports'), 'url' => 'modules/laporan/index.php',
             'roles' => ['superadmin', 'admin', 'registrasi', 'dokter', 'farmasi', 'kasir'],
             'children' => [
                 ['label' => t('menu.operational'), 'url' => 'modules/laporan/index.php?g=' . urlencode('Operasional'),
                  'lap_group' => 'Operasional', 'roles' => ['registrasi', 'dokter', 'kasir', 'admin', 'superadmin']],
                 ['label' => t('menu.financial'), 'url' => 'modules/laporan/index.php?g=' . urlencode('Keuangan'),
                  'lap_group' => 'Keuangan', 'roles' => ['kasir', 'admin', 'superadmin']],
                 ['label' => t('menu.support'), 'url' => 'modules/laporan/index.php?g=' . urlencode('Penunjang'),
                  'lap_group' => 'Penunjang', 'roles' => ['dokter', 'farmasi', 'superadmin']],
             ]],
        ]],
        ['grup' => t('menu.groups.pengaturan'), 'items' => [
            ['ico' => 'hospital', 'label' => t('menu.clinic_profile'), 'url' => 'modules/pengaturan/profil.php',
             'roles' => ['superadmin'], 'match' => 'pengaturan/profil'],
            ['ico' => 'users', 'label' => t('menu.users_roles'), 'url' => 'modules/pengaturan/users.php',
             'roles' => ['superadmin'], 'match' => 'pengaturan/user'],
        ]],
    ];
}
