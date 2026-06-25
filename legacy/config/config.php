<?php
/**
 * Konfigurasi umum aplikasi SIM Klinik
 */

// Info aplikasi
define('APP_NAME', 'SIM Klinik');
define('APP_FULL', 'Sistem Informasi Manajemen Klinik');

// Profil klinik dibaca dari tabel `setting` (bisa diedit di menu Pengaturan),
// fallback ke nilai default bila DB/tabel belum siap.
require_once __DIR__ . '/database.php';
if (! function_exists('app_settings')) {
function app_settings(): array
{
    static $s = null;
    if ($s === null) {
        $s = [];
        try {
            foreach (db()->query("SELECT k, v FROM setting") as $r) $s[$r['k']] = $r['v'];
        } catch (Throwable $e) { /* tabel belum ada -> pakai default */ }
    }
    return $s;
}
}
if (! function_exists('app_setting')) {
function app_setting(string $key, string $default = ''): string
{
    $s = app_settings();
    return $s[$key] ?? $default;
}
}
define('CLINIC_NAME', app_setting('clinic_name', 'PT Sapta Genki Clinic'));
define('CLINIC_UNIT', app_setting('clinic_unit', 'Unit Bayuharta — Karawang'));
define('CLINIC_ADDRESS', app_setting('clinic_address', 'Karawang, Jawa Barat'));
// Path relatif logo klinik (mis. 'uploads/clinic_logo_xxx.png'); kosong = belum diunggah.
define('CLINIC_LOGO', app_setting('clinic_logo', ''));

// BASE_URL otomatis (folder tempat aplikasi diletakkan di htdocs)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
// Naikkan ke root aplikasi bila script berada di subfolder modules/...
$rootPos = strpos($scriptDir, '/modules');
if ($rootPos !== false) {
    $scriptDir = substr($scriptDir, 0, $rootPos);
}
$rootPos = strpos($scriptDir, '/auth');
if ($rootPos !== false) {
    $scriptDir = substr($scriptDir, 0, $rootPos);
}
define('BASE_URL', rtrim($scriptDir, '/') . '/');

// Path direktori (filesystem)
define('ROOT_PATH', dirname(__DIR__));
define('APP_ROOT', dirname(ROOT_PATH));
// Upload disimpan di public/uploads agar bisa diakses via URL /uploads/...
define('UPLOAD_PATH', APP_ROOT . '/public/uploads');
// Static assets (CSS/JS/vendor) ada di simklinik-frontend
define('ASSETS_FS_PATH', dirname(APP_ROOT) . '/simklinik-frontend/public/assets');

// Zona waktu
date_default_timezone_set('Asia/Jakarta');

// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
