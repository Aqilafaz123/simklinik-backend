<?php
/**
 * Seed contoh: 3 data Kategori Diagnostik, Pemeriksaan Diagnostik,
 * Kategori Fisioterapi, dan Layanan Fisioterapi.
 * Idempoten: tidak menduplikasi bila nama sudah ada.
 *   Jalankan: php database/seed_diag_fisio.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/master_lib.php';

$pdo = db();

/** Ambil id kategori (buat bila belum ada) */
function kategori_id(PDO $pdo, string $table, string $nama): int
{
    $s = $pdo->prepare("SELECT id FROM {$table} WHERE nama = ?");
    $s->execute([$nama]);
    if ($id = $s->fetchColumn()) return (int) $id;
    $pdo->prepare("INSERT INTO {$table} (nama) VALUES (?)")->execute([$nama]);
    return (int) $pdo->lastInsertId();
}

/** Insert pemeriksaan/layanan (kode auto) bila nama belum ada di tabel */
function tambah_item(PDO $pdo, string $table, string $jenis, int $katId, string $nama, float $tarif): string
{
    $s = $pdo->prepare("SELECT kode FROM {$table} WHERE nama = ?");
    $s->execute([$nama]);
    if ($kode = $s->fetchColumn()) return $kode . ' (sudah ada)';
    $kode = generate_item_code($jenis, $table);
    $pdo->prepare("INSERT INTO {$table} (kategori_id,kode,nama,tarif,status) VALUES (?,?,?,?, 'aktif')")
        ->execute([$katId, $kode, $nama, $tarif]);
    return $kode;
}

// ---------- DIAGNOSTIK ----------
$dKardio = kategori_id($pdo, 'diag_kategori', 'Kardiologi');
$dRespi  = kategori_id($pdo, 'diag_kategori', 'Respirasi');
$dNeuro  = kategori_id($pdo, 'diag_kategori', 'Neurologi');
echo "Kategori Diagnostik  : Kardiologi, Respirasi, Neurologi\n";

$diag = [
    ['EKG (Elektrokardiogram)', $dKardio, 150000],
    ['Treadmill Test',          $dKardio, 450000],
    ['Spirometri',              $dRespi,  200000],
];
foreach ($diag as [$nm, $kat, $tr]) {
    echo "  Pemeriksaan Diagnostik: " . tambah_item($pdo, 'diag_pemeriksaan', 'diagnostik', $kat, $nm, $tr) . " - {$nm}\n";
}

// ---------- FISIOTERAPI ----------
$fElektro = kategori_id($pdo, 'fisio_kategori', 'Elektroterapi');
$fLatihan = kategori_id($pdo, 'fisio_kategori', 'Terapi Latihan');
$fManual  = kategori_id($pdo, 'fisio_kategori', 'Terapi Manual');
echo "Kategori Fisioterapi : Elektroterapi, Terapi Latihan, Terapi Manual\n";

$fisio = [
    ['Infra Red (IR)',            $fElektro, 75000],
    ['TENS',                      $fElektro, 100000],
    ['Terapi Latihan (Exercise)', $fLatihan, 120000],
];
foreach ($fisio as [$nm, $kat, $tr]) {
    echo "  Layanan Fisioterapi   : " . tambah_item($pdo, 'fisio_pemeriksaan', 'fisioterapi', $kat, $nm, $tr) . " - {$nm}\n";
}

echo "Selesai.\n";
