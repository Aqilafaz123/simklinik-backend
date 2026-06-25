<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/reports.php';
require_login();
$pageTitle = t('pages.reports');
$role = current_role();

// rentang default: awal bulan s/d hari ini
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

// Catatan: jangan pakai nama $grup/$items di sini — header.php memakai nama itu
// untuk menu sidebar (variabel global) sehingga akan saling menimpa.
// Hanya tampilkan laporan yang boleh diakses role ini.
$grupLaporan = [];   // grup => [slug => konfigurasi laporan]
foreach (laporan_list_for($role) as $slug => $r) {
    $grupLaporan[$r['group']][$slug] = $r;
}
$namaGrupList = array_keys($grupLaporan);

// Role tanpa laporan apa pun -> tampilkan pesan kosong.
if (!$namaGrupList) {
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="page-toolbar"><div><div class="pt-title">' . e(t('pages.reports')) . '</div>'
       . '<div class="pt-sub">' . e(t('common.reports_empty')) . '</div></div></div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Tentukan laporan + grup yang aktif (abaikan laporan di luar hak akses role).
$activeSlug = $_GET['jenis'] ?? '';
$cfg = laporan_can($role, $activeSlug) ? laporan_get($activeSlug) : null;
if ($cfg) {
    $activeGroup = $cfg['group'];
} else {
    $activeGroup = $_GET['g'] ?? $namaGrupList[0];
    if (!isset($grupLaporan[$activeGroup])) $activeGroup = $namaGrupList[0];
    $activeSlug = array_key_first($grupLaporan[$activeGroup]);
    $cfg = laporan_get($activeSlug);
}

// Jalankan laporan aktif + hitung total kolom 'sum'
$rows = laporan_run($cfg, $dari, $sampai);
$totals = array_fill_keys($cfg['sum'], 0);
foreach ($rows as $r) {
    foreach ($cfg['sum'] as $c) $totals[$c] += (float) ($r[$c] ?? 0);
}
// Jumlah baris per laporan di grup aktif (untuk badge di tab). Laporan aktif
// pakai $rows yang sudah dijalankan; lainnya dijalankan sekali untuk dihitung.
$countRep = [];
foreach ($grupLaporan[$activeGroup] as $s => $r) {
    $countRep[$s] = ($s === $activeSlug)
        ? count($rows)
        : count(laporan_run(laporan_get($s), $dari, $sampai));
}

$qs = 'jenis=' . urlencode($activeSlug) . '&dari=' . urlencode($dari) . '&sampai=' . urlencode($sampai);

$pageTitle = t('pages.reports_group', ['group' => $activeGroup]);

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= e(t('pages.reports_group', ['group' => $activeGroup])) ?></div>
    <div class="pt-sub"><?= e(t('common.reports_sub')) ?></div>
  </div>
</div>

<!-- Panel laporan: tab vertikal (jenis) di kiri + konten di kanan -->
<div class="master-split" style="margin-top:18px">
  <nav class="vtabs">
    <?php foreach ($grupLaporan[$activeGroup] as $s => $r): $on = $s === $activeSlug; ?>
      <a class="vtab<?= $on ? ' active' : '' ?>"
         href="<?= legacy_url('modules/laporan/index.php?jenis=' . $s . '&dari=' . e($dari) . '&sampai=' . e($sampai)) ?>">
        <span class="vt-main"><span class="vt-ico"><?= $r['icon'] ?? '' ?></span><span><?= e($r['label']) ?></span></span>
        <span class="tab-count"><?= number_format($countRep[$s], 0, ',', '.') ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="table-wrap" style="flex:1;min-width:0;margin:0">
  <div class="panel-toolbar">
    <form method="get" class="report-filter no-print">
      <input type="hidden" name="jenis" value="<?= e($activeSlug) ?>">
      <div class="form-group" style="margin:0"><label><?= e(t('common.from_date')) ?></label><input type="date" name="dari" value="<?= e($dari) ?>" class="form-control" onchange="this.form.submit()"></div>
      <div class="form-group" style="margin:0"><label><?= e(t('common.to_date')) ?></label><input type="date" name="sampai" value="<?= e($sampai) ?>" class="form-control" onchange="this.form.submit()"></div>
    </form>
    <div class="report-actions no-print">
      <a class="btn btn-light" href="<?= legacy_url('modules/laporan/export.php?' . $qs) ?>"><?= app_icon('download') ?> <?= e(t('common.export_csv')) ?></a>
      <button type="button" class="btn btn-light" onclick="window.print()"><?= app_icon('print') ?> <?= e(t('common.print')) ?></button>
    </div>
  </div>

  <div class="print-head" style="display:none">
    <h2><?= CLINIC_NAME ?> — <?= e($cfg['label']) ?></h2>
    <p><?= e(t('common.period')) ?>: <?= tgl_id($dari) ?> <?= e(t('common.period_to')) ?> <?= tgl_id($sampai) ?></p>
  </div>

  <table class="datatable dt-noscroll<?= in_array($activeSlug, ['kunjungan', 'poli'], true) ? ' no-auto-num' : '' ?>" style="width:100%">
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
      <tr class="report-total-row">
        <?php $first = true; foreach ($cfg['cols'] as $key => $c): ?>
          <?php if ($first): $first = false; ?>
            <td><?= e(t('common.total')) ?></td>
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
  </div><!-- /.table-wrap -->
</div><!-- /.master-split -->

<style>
@media print{
  .sidebar,.topbar,.footer,.no-print,.group-strip,.panel-tabs,.vtabs,
  .dt-search,.dt-length,.dt-paging,.dt-info{display:none !important}
  .master-split{display:block}
  .main{margin-left:0}
  .print-head{display:block !important;margin-bottom:10px}
  .btn{display:none}
}
</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
