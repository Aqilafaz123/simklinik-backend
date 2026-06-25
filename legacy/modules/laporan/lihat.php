<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/reports.php';
require_login();

$slug = $_GET['jenis'] ?? '';
$cfg = laporan_get($slug);
if (!$cfg) { set_flash('danger', 'Jenis laporan tidak dikenal.'); legacy_redirect('modules/laporan/index.php'); }
if (!laporan_can(current_role(), $slug)) { set_flash('danger', 'Anda tidak memiliki akses ke laporan tersebut.'); legacy_redirect('modules/laporan/index.php'); }
$pageTitle = $cfg['label'];

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

$rows = laporan_run($cfg, $dari, $sampai);

// hitung total kolom 'sum'
$totals = array_fill_keys($cfg['sum'], 0);
foreach ($rows as $r) {
    foreach ($cfg['sum'] as $c) $totals[$c] += (float) ($r[$c] ?? 0);
}

$qs = 'jenis=' . urlencode($slug) . '&dari=' . urlencode($dari) . '&sampai=' . urlencode($sampai);
require_once __DIR__ . '/../../includes/header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
  <div>
    <a href="<?= legacy_url('modules/laporan/index.php?dari=' . e($dari) . '&sampai=' . e($sampai)) ?>" class="btn btn-light btn-sm"><?= app_icon("arrowleft") ?> Daftar Laporan</a>
    <h2 style="display:inline-block;margin:0 0 0 8px"><?= $cfg['icon'] ?> <?= e($cfg['label']) ?></h2>
  </div>
  <div style="display:flex;gap:8px">
    <a class="btn btn-light" href="<?= legacy_url('modules/laporan/export.php?' . $qs) ?>"><?= app_icon("download") ?> Export CSV</a>
    <button class="btn btn-light" onclick="window.print()"><?= app_icon("print") ?> Cetak / PDF</button>
  </div>
</div>

<form method="get" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-top:14px" class="no-print">
  <input type="hidden" name="jenis" value="<?= e($slug) ?>">
  <div class="form-group" style="margin:0"><label>Dari</label><input type="date" name="dari" value="<?= e($dari) ?>" class="form-control"></div>
  <div class="form-group" style="margin:0"><label>Sampai</label><input type="date" name="sampai" value="<?= e($sampai) ?>" class="form-control"></div>
  <button class="btn" type="submit">Terapkan</button>
</form>

<div class="print-head" style="display:none">
  <h2><?= CLINIC_NAME ?> — <?= e($cfg['label']) ?></h2>
  <p>Periode: <?= tgl_id($dari) ?> s/d <?= tgl_id($sampai) ?></p>
</div>

<div class="section-title">Periode <?= tgl_id($dari) ?> – <?= tgl_id($sampai) ?> &middot; <?= count($rows) ?> baris</div>
<div class="table-wrap">
  <table class="datatable" style="width:100%">
    <thead>
      <tr><?php foreach ($cfg['cols'] as $c): ?><th><?= e($c[0]) ?></th><?php endforeach; ?></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <?php foreach ($cfg['cols'] as $key => $c): ?>
            <td <?= in_array($c[1], ['money','number'], true) ? 'style="text-align:right"' : '' ?>>
              <?= laporan_cell_html($r[$key] ?? '', $c[1]) ?>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <?php if ($cfg['sum']): ?>
    <tfoot>
      <tr style="font-weight:700;background:#f8fafc">
        <?php $first = true; foreach ($cfg['cols'] as $key => $c): ?>
          <?php if ($first): $first = false; ?>
            <td>TOTAL</td>
          <?php elseif (in_array($key, $cfg['sum'], true)): ?>
            <td style="text-align:right"><?= laporan_cell_html($totals[$key], $c[1]) ?></td>
          <?php else: ?>
            <td></td>
          <?php endif; ?>
        <?php endforeach; ?>
      </tr>
    </tfoot>
    <?php endif; ?>
  </table>
</div>

<style>
@media print{
  .sidebar,.topbar,.footer,.no-print,.dataTables_filter,.dataTables_length,.dataTables_paginate,.dataTables_info{display:none !important}
  .main{margin-left:0}
  .print-head{display:block !important;margin-bottom:10px}
  .btn{display:none}
}
</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
