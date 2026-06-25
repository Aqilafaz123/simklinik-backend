<?php
/**
 * Definisi menu aplikasi (struktur menu sesuai blueprint) + hak akses per role.
 * 'roles' = daftar role yang boleh melihat menu. Superadmin selalu melihat semua.
 */
function get_menu(): array
{
    $opsRoles = ['superadmin', 'admin', 'registrasi'];
    $billRoles = ['superadmin', 'admin', 'kasir'];

    return [
        ['grup' => null, 'items' => [
            ['ico' => 'dashboard', 'label' => 'Dashboard', 'url' => 'modules/dashboard/index.php',
             'roles' => ['superadmin', 'admin', 'registrasi', 'dokter', 'farmasi', 'kasir']],
        ]],
        ['grup' => 'Rekam Medis', 'items' => [
            ['ico' => 'rekam', 'label' => 'Rekam Medis', 'url' => 'modules/rekam_medis/index.php',
             'roles' => ['superadmin', 'dokter']],
        ]],
        ['grup' => 'Operasional', 'items' => [
            ['ico' => 'calendar', 'label' => 'Daftar Kunjungan', 'url' => 'modules/registrasi/index.php',
             'roles' => $opsRoles, 'match' => 'registrasi/index'],
            ['ico' => 'registrasi', 'label' => 'Pendaftaran Baru', 'url' => 'modules/registrasi/daftar.php',
             'roles' => $opsRoles, 'match' => 'registrasi/daftar'],
            ['ico' => 'users', 'label' => 'Data Pasien', 'url' => 'modules/registrasi/pasien.php',
             'roles' => $opsRoles, 'match' => 'registrasi/pasien'],
            ['ico' => 'ticket', 'label' => 'Papan Antrian', 'url' => 'modules/registrasi/antrian.php',
             'roles' => ['superadmin', 'registrasi'], 'match' => 'registrasi/antrian'],
            ['ico' => 'pelayanan', 'label' => 'Pelayanan', 'url' => 'modules/pelayanan/index.php',
             'roles' => ['dokter', 'farmasi'],
             'children' => [
                 ['label' => 'Antrian Pemeriksaan', 'url' => 'modules/pelayanan/index.php', 'roles' => ['dokter']],
                 ['label' => 'Antrian Farmasi', 'url' => 'modules/pelayanan/farmasi.php', 'roles' => ['farmasi']],
             ]],
        ]],
        ['grup' => 'Keuangan', 'items' => [
            ['ico' => 'billing', 'label' => 'Billing', 'url' => 'modules/billing/index.php',
             'roles' => $billRoles],
            ['ico' => 'keuangan', 'label' => 'Keuangan', 'url' => 'modules/keuangan/index.php',
             'roles' => $billRoles],
        ]],
        ['grup' => 'Data & Stok', 'items' => [
            ['ico' => 'master', 'label' => 'Master Data', 'url' => 'modules/master/index.php',
             'roles' => ['superadmin'],
             'children' => [
                 ['label' => 'Layanan & Tarif', 'url' => 'modules/master/index.php?g=' . urlencode('Layanan & Tarif'), 'badge_group' => 'Layanan & Tarif'],
                 ['label' => 'SDM & Poli', 'url' => 'modules/master/index.php?g=' . urlencode('SDM & Poli'), 'badge_group' => 'SDM & Poli'],
                 ['label' => 'Farmasi', 'url' => 'modules/master/index.php?g=' . urlencode('Farmasi'), 'badge_group' => 'Farmasi'],
                 ['label' => 'Penjamin & Bank', 'url' => 'modules/master/index.php?g=' . urlencode('Penjamin & Bank'), 'badge_group' => 'Penjamin & Bank'],
                 ['label' => 'Pasien', 'url' => 'modules/master/index.php?g=' . urlencode('Pasien'), 'badge_group' => 'Pasien'],
                 ['label' => 'Kode Pembatalan', 'url' => 'modules/master/index.php?g=' . urlencode('Billing'), 'badge_group' => 'Billing'],
             ]],
            ['ico' => 'inventory', 'label' => 'Inventory', 'url' => 'modules/inventory/index.php',
             'roles' => ['superadmin', 'farmasi']],
        ]],
        ['grup' => 'Lainnya', 'items' => [
            ['ico' => 'laporan', 'label' => 'Laporan', 'url' => 'modules/laporan/index.php',
             'roles' => ['superadmin', 'admin', 'registrasi', 'dokter', 'farmasi', 'kasir'],
             'children' => [
                 ['label' => 'Operasional', 'url' => 'modules/laporan/index.php?g=' . urlencode('Operasional'),
                  'lap_group' => 'Operasional', 'roles' => ['registrasi', 'dokter', 'kasir', 'admin', 'superadmin']],
                 ['label' => 'Keuangan', 'url' => 'modules/laporan/index.php?g=' . urlencode('Keuangan'),
                  'lap_group' => 'Keuangan', 'roles' => ['kasir', 'admin', 'superadmin']],
                 ['label' => 'Penunjang', 'url' => 'modules/laporan/index.php?g=' . urlencode('Penunjang'),
                  'lap_group' => 'Penunjang', 'roles' => ['dokter', 'farmasi', 'superadmin']],
             ]],
        ]],
        ['grup' => 'Pengaturan', 'items' => [
            ['ico' => 'hospital', 'label' => 'Profil Klinik', 'url' => 'modules/pengaturan/profil.php',
             'roles' => ['superadmin'], 'match' => 'pengaturan/profil'],
            ['ico' => 'users', 'label' => 'Pengguna & Role', 'url' => 'modules/pengaturan/users.php',
             'roles' => ['superadmin'], 'match' => 'pengaturan/user'],
        ]],
    ];
}
