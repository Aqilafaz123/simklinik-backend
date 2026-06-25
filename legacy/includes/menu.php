<?php
/**
 * Definisi menu aplikasi (struktur menu sesuai blueprint) + hak akses per role.
 * 'roles' = daftar role yang boleh melihat menu. Admin selalu melihat semua.
 */
function get_menu(): array
{
    return [
        ['grup' => null, 'items' => [
            ['ico' => 'dashboard', 'label' => 'Dashboard', 'url' => 'modules/dashboard/index.php',
             'roles' => ['admin', 'registrasi', 'dokter', 'farmasi', 'kasir']],
        ]],
        ['grup' => 'Operasional', 'items' => [
            ['ico' => 'registrasi', 'label' => 'Registrasi',  'url' => 'modules/registrasi/index.php',
             'roles' => ['admin', 'registrasi'],
             'children' => [
                 ['label' => 'Daftar Kunjungan', 'url' => 'modules/registrasi/index.php'],
                 ['label' => 'Pendaftaran Baru', 'url' => 'modules/registrasi/daftar.php'],
                 ['label' => 'Data Pasien',      'url' => 'modules/registrasi/pasien.php'],
                 ['label' => 'Papan Antrian',    'url' => 'modules/registrasi/antrian.php'],
             ]],
            ['ico' => 'pelayanan',  'label' => 'Pelayanan',   'url' => 'modules/pelayanan/index.php',
             'roles' => ['admin', 'dokter', 'farmasi'],
             'children' => [
                 ['label' => 'Antrian Pemeriksaan', 'url' => 'modules/pelayanan/index.php', 'roles' => ['admin', 'dokter']],
                 ['label' => 'Antrian Farmasi',     'url' => 'modules/pelayanan/farmasi.php', 'roles' => ['admin', 'farmasi']],
             ]],
            ['ico' => 'rekam',      'label' => 'Rekam Medis', 'url' => 'modules/rekam_medis/index.php',
             'roles' => ['admin', 'dokter']],
        ]],
        ['grup' => 'Keuangan', 'items' => [
            ['ico' => 'billing',  'label' => 'Billing',  'url' => 'modules/billing/index.php',
             'roles' => ['admin', 'kasir']],
            ['ico' => 'keuangan', 'label' => 'Keuangan', 'url' => 'modules/keuangan/index.php',
             'roles' => ['admin', 'kasir']],
        ]],
        ['grup' => 'Data & Stok', 'items' => [
            ['ico' => 'master',    'label' => 'Master Data', 'url' => 'modules/master/index.php',
             'roles' => ['admin'],
             'children' => [
                 ['label' => 'Layanan & Tarif', 'url' => 'modules/master/index.php?g=' . urlencode('Layanan & Tarif'), 'badge_group' => 'Layanan & Tarif'],
                 ['label' => 'SDM & Poli',      'url' => 'modules/master/index.php?g=' . urlencode('SDM & Poli'),      'badge_group' => 'SDM & Poli'],
                 ['label' => 'Farmasi',         'url' => 'modules/master/index.php?g=' . urlencode('Farmasi'),         'badge_group' => 'Farmasi'],
                 ['label' => 'Penjamin & Bank', 'url' => 'modules/master/index.php?g=' . urlencode('Penjamin & Bank'), 'badge_group' => 'Penjamin & Bank'],
                 ['label' => 'Pasien',          'url' => 'modules/master/index.php?g=' . urlencode('Pasien'),          'badge_group' => 'Pasien'],
             ]],
            ['ico' => 'inventory', 'label' => 'Inventory',   'url' => 'modules/inventory/index.php',
             'roles' => ['admin', 'farmasi']],
        ]],
        ['grup' => 'Lainnya', 'items' => [
            ['ico' => 'laporan',    'label' => 'Laporan',    'url' => 'modules/laporan/index.php',
             'roles' => ['admin', 'registrasi', 'dokter', 'farmasi', 'kasir'],
             'children' => [
                 // Tiap anak = satu kelompok laporan. 'roles' = role yang punya
                 // minimal satu laporan di kelompok itu (admin selalu lihat semua).
                 ['label' => 'Operasional', 'url' => 'modules/laporan/index.php?g=' . urlencode('Operasional'),
                  'lap_group' => 'Operasional', 'roles' => ['registrasi', 'dokter', 'kasir']],
                 ['label' => 'Keuangan',    'url' => 'modules/laporan/index.php?g=' . urlencode('Keuangan'),
                  'lap_group' => 'Keuangan',    'roles' => ['kasir']],
                 ['label' => 'Penunjang',   'url' => 'modules/laporan/index.php?g=' . urlencode('Penunjang'),
                  'lap_group' => 'Penunjang',   'roles' => ['dokter', 'farmasi']],
             ]],
        ]],
        ['grup' => 'Pengaturan', 'items' => [
            ['ico' => 'hospital', 'label' => 'Profil Klinik',   'url' => 'modules/pengaturan/profil.php',
             'roles' => ['admin'], 'match' => 'pengaturan/profil'],
            ['ico' => 'users',    'label' => 'Pengguna & Role', 'url' => 'modules/pengaturan/users.php',
             'roles' => ['admin'], 'match' => 'pengaturan/user'],
        ]],
    ];
}