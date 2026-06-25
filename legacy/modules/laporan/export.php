<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/reports.php';
require_login();

$slug = $_GET['jenis'] ?? '';
$cfg = laporan_get($slug);
if (!$cfg) { set_flash('danger', 'Jenis laporan tidak dikenal.'); legacy_redirect('modules/laporan/index.php'); }
if (!laporan_can(current_role(), $slug)) { set_flash('danger', 'Anda tidak memiliki akses ke laporan tersebut.'); legacy_redirect('modules/laporan/index.php'); }

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$rows = laporan_run($cfg, $dari, $sampai);

$totals = array_fill_keys($cfg['sum'], 0);
foreach ($rows as $r) foreach ($cfg['sum'] as $c) $totals[$c] += (float) ($r[$c] ?? 0);

$filename = 'laporan_' . $slug . '_' . $dari . '_sd_' . $sampai . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fprintf($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

// judul
fputcsv($out, [CLINIC_NAME . ' - ' . $cfg['label']]);
fputcsv($out, ['Periode', $dari . ' s/d ' . $sampai]);
fputcsv($out, []);

// header kolom
fputcsv($out, array_map(fn($c) => $c[0], $cfg['cols']));

// baris data
foreach ($rows as $r) {
    $line = [];
    foreach ($cfg['cols'] as $key => $c) {
        $line[] = laporan_cell_csv($r[$key] ?? '', $c[1]);
    }
    fputcsv($out, $line);
}

// baris total
if ($cfg['sum']) {
    $line = [];
    $first = true;
    foreach ($cfg['cols'] as $key => $c) {
        if ($first) { $line[] = 'TOTAL'; $first = false; }
        elseif (in_array($key, $cfg['sum'], true)) $line[] = laporan_cell_csv($totals[$key], $c[1]);
        else $line[] = '';
    }
    fputcsv($out, $line);
}

fclose($out);
exit;
