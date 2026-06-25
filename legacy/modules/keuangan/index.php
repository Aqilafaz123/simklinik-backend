<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('kasir');
$pageTitle = 'Keuangan';
$tgl = $_GET['tgl'] ?? date('Y-m-d');

$stmt = db()->prepare(
    "SELECT k.id, k.no_kunjungan, k.no_antrian, k.status, k.jenis_penjamin,
            p.no_mr, p.nama AS pasien, po.kode AS poli_kode,
            b.total AS tagihan,
            i.no_invoice, i.terbayar, i.status AS inv_status, i.id AS invoice_id
     FROM kunjungan k
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     JOIN billing b ON b.kunjungan_id = k.id AND b.status='final'
     LEFT JOIN invoice i ON i.kunjungan_id = k.id
     WHERE k.tgl_kunjungan = ? AND k.status IN ('pembayaran','selesai')
     ORDER BY k.id DESC");
$stmt->execute([$tgl]);
$rows = $stmt->fetchAll();

// Ringkasan hari ini
$totalTagihan = 0; $totalBayar = 0; $piutang = 0;
foreach ($rows as $r) {
    $totalTagihan += (float) $r['tagihan'];
    $totalBayar   += (float) ($r['terbayar'] ?? 0);
    $piutang      += (float) $r['tagihan'] - (float) ($r['terbayar'] ?? 0);
}

$invBadge = ['belum_bayar' => 'badge-red', 'sebagian' => 'badge-orange', 'lunas' => 'badge-green'];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title">Keuangan</div>
    <div class="pt-sub"><?= tgl_id($tgl) ?> &middot; <?= count($rows) ?> tagihan</div>
  </div>
  <div class="pt-actions">
    <form method="get" class="toolbar-filter">
      <span class="ico"><?= app_icon('calendar') ?></span>
      <input type="date" name="tgl" value="<?= e($tgl) ?>" class="form-control" onchange="this.form.submit()">
    </form>
  </div>
</div>

<div class="cards" style="margin-top:16px">
  <div class="card stat"><div><div class="num"><?= rupiah($totalTagihan) ?></div><div class="lbl">Total Tagihan</div></div><div class="ico bg-blue"><?= app_icon('billing') ?></div></div>
  <div class="card stat"><div><div class="num"><?= rupiah($totalBayar) ?></div><div class="lbl">Sudah Terbayar</div></div><div class="ico bg-green"><?= app_icon('money') ?></div></div>
  <div class="card stat"><div><div class="num"><?= rupiah($piutang) ?></div><div class="lbl">Piutang / Belum Lunas</div></div><div class="ico bg-red"><?= app_icon('keuangan') ?></div></div>
</div>

<div class="section-title">Tagihan — <?= tgl_id($tgl) ?></div>
<div class="table-wrap">
  <table class="datatable dt-noscroll no-auto-num" style="width:100%">
    <thead>
      <tr><th>Antrian</th><th>No. Invoice</th><th>No. MR</th><th>Pasien</th><th>Penjamin</th>
          <th style="text-align:right">Tagihan</th><th style="text-align:right">Sisa</th>
          <th>Status</th><th class="col-actions">Aksi</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): $sisa = (float) $r['tagihan'] - (float) ($r['terbayar'] ?? 0); ?>
        <tr>
          <td><b><?= e($r['poli_kode']) ?>-<?= str_pad($r['no_antrian'], 3, '0', STR_PAD_LEFT) ?></b></td>
          <td><?= e($r['no_invoice'] ?? '-') ?></td>
          <td><?= e($r['no_mr']) ?></td>
          <td><?= e($r['pasien']) ?></td>
          <td><span class="badge badge-gray"><?= e(strtoupper($r['jenis_penjamin'])) ?></span></td>
          <td style="text-align:right"><?= rupiah($r['tagihan']) ?></td>
          <td style="text-align:right"><?= rupiah($sisa) ?></td>
          <td style="text-align:center;"><span class="badge <?= $invBadge[$r['inv_status'] ?? 'belum_bayar'] ?? 'badge-gray' ?>">
              <?= e(ucwords(str_replace('_', ' ', $r['inv_status'] ?? 'belum bayar'))) ?></span></td>
          <td class="cell-actions"><div class="cell-actions-inner">
            <a class="btn btn-sm" href="<?= legacy_url('modules/keuangan/bayar.php?kunjungan_id=' . $r['id']) ?>"><?= ($r['inv_status'] ?? '') === 'lunas' ? 'Lihat' : app_icon('money') . ' Bayar' ?></a>
            <?php if ($r['invoice_id']): ?><a class="btn btn-sm btn-light btn-icon" target="_blank" href="<?= legacy_url('modules/keuangan/struk.php?invoice_id=' . $r['invoice_id']) ?>" title="Cetak struk"><?= app_icon('print') ?></a><?php endif; ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
