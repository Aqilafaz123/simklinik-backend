<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('kasir');
$pageTitle = 'Billing';
$tgl = $_GET['tgl'] ?? date('Y-m-d');

$stmt = db()->prepare(
    "SELECT k.id, k.no_kunjungan, k.no_antrian, k.status,
            p.no_mr, p.nama AS pasien, po.kode AS poli_kode, po.nama AS poli,
            b.total AS billing_total, b.status AS billing_status
     FROM kunjungan k
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN billing b ON b.kunjungan_id = k.id
     WHERE k.tgl_kunjungan = ? AND k.status IN ('billing','pembayaran','selesai')
     ORDER BY k.id DESC");
$stmt->execute([$tgl]);
$rows = $stmt->fetchAll();

$badge = ['billing' => 'badge-orange', 'pembayaran' => 'badge-blue', 'selesai' => 'badge-green'];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title">Billing</div>
    <div class="pt-sub"><?= tgl_id($tgl) ?> &middot; <?= count($rows) ?> tagihan</div>
  </div>
  <div class="pt-actions">
    <form method="get" class="toolbar-filter">
      <span class="ico"><?= app_icon('calendar') ?></span>
      <input type="date" name="tgl" value="<?= e($tgl) ?>" class="form-control" onchange="this.form.submit()">
    </form>
  </div>
</div>

<div class="table-wrap">
  <table class="datatable dt-noscroll no-auto-num" style="width:100%">
    <thead>
      <tr><th>Antrian</th><th>No. Kunjungan</th><th>No. MR</th><th>Pasien</th><th>Poli</th>
          <th>Status</th><th style="text-align:right">Total Tagihan</th><th class="col-actions">Aksi</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><b><?= e($r['poli_kode']) ?>-<?= str_pad($r['no_antrian'], 3, '0', STR_PAD_LEFT) ?></b></td>
          <td><?= e($r['no_kunjungan']) ?></td>
          <td><?= e($r['no_mr']) ?></td>
          <td><?= e($r['pasien']) ?></td>
          <td><?= e($r['poli']) ?></td>
          <td><span class="badge <?= $badge[$r['status']] ?? 'badge-gray' ?>"><?= e(ucfirst($r['status'])) ?></span></td>
          <td style="text-align:right"><?= $r['billing_total'] !== null ? rupiah($r['billing_total']) : '<span style="color:var(--muted)">belum</span>' ?></td>
          <td class="cell-actions"><div class="cell-actions-inner"><?php if ($r['status'] === 'billing'): ?>
            <a class="btn btn-sm" href="<?= legacy_url('modules/billing/proses.php?kunjungan_id=' . $r['id']) ?>"><?= app_icon('billing') ?> Buat Billing</a>
          <?php else: ?>
            <a class="btn btn-sm btn-light" href="<?= legacy_url('modules/billing/detail.php?kunjungan_id=' . $r['id']) ?>"
               data-modal-url="<?= legacy_url('modules/billing/detail.php?kunjungan_id=' . $r['id'] . '&modal=1') ?>"
               data-modal-title="Detail Tagihan"><?= app_icon('eye') ?> Lihat Detail</a>
          <?php endif; ?></div></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal detail tagihan (premium, read-only) -->
<div class="modal-overlay" id="billModal" aria-hidden="true">
  <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="billModalTitle">
    <div class="modal-head">
      <div class="modal-title" id="billModalTitle">Detail Tagihan</div>
      <button type="button" class="modal-close" data-modal-close aria-label="Tutup">&times;</button>
    </div>
    <div class="modal-body" id="billModalBody"></div>
  </div>
</div>
<script>
(function () {
  var overlay = document.getElementById('billModal');
  var box     = document.getElementById('billModalBody');
  var titleEl = document.getElementById('billModalTitle');

  function open(url, title) {
    titleEl.textContent = title || 'Detail Tagihan';
    box.innerHTML = '<div class="modal-loading">Memuat…</div>';
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    fetch(url, { headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { return r.text(); })
      .then(function (html) { box.innerHTML = html; });
  }
  function close() {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
    box.innerHTML = '';
  }
  document.addEventListener('click', function (ev) {
    var opener = ev.target.closest('[data-modal-url]');
    if (opener) { ev.preventDefault(); open(opener.getAttribute('data-modal-url'), opener.getAttribute('data-modal-title')); return; }
    if (ev.target.closest('[data-modal-close]') || ev.target === overlay) close();
  });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape' && overlay.classList.contains('open')) close();
  });
})();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
