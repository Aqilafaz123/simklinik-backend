<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/billing_lib.php';
require_role('kasir');
$pageTitle = 'Proses Billing';

$kunjunganId = (int) ($_GET['kunjungan_id'] ?? $_POST['kunjungan_id'] ?? 0);

$kj = db()->prepare(
    "SELECT k.*, p.no_mr, p.nama AS pasien, po.nama AS poli, d.nama AS dokter
     FROM kunjungan k
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN dokter d ON d.id = k.dokter_id
     WHERE k.id = ?");
$kj->execute([$kunjunganId]);
$kj = $kj->fetch();
if (!$kj) { set_flash('danger', 'Kunjungan tidak ditemukan.'); legacy_redirect('modules/billing/index.php'); }

// Billing yang sudah ada
$billing = db()->prepare("SELECT * FROM billing WHERE kunjungan_id=?");
$billing->execute([$kunjunganId]);
$billing = $billing->fetch() ?: null;
$isFinal = $billing && $billing['status'] === 'final';

// administrasi tersimpan (jika ada)
$admTersimpanRow = db()->prepare(
    "SELECT bd.subtotal FROM billing_detail bd JOIN billing b ON b.id=bd.billing_id
     WHERE b.kunjungan_id=? AND bd.kategori='administrasi' LIMIT 1");
$admTersimpanRow->execute([$kunjunganId]);
$administrasi = (float) ($admTersimpanRow->fetchColumn() ?: 0);
$diskon = (float) ($billing['diskon'] ?? 0);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isFinal) {
    sim_csrf_verify();
    $aksi = $_POST['aksi'] ?? 'simpan';
    $administrasi = max(0, (float) ($_POST['administrasi'] ?? 0));
    $diskon = max(0, (float) ($_POST['diskon'] ?? 0));

    $lines = collect_billing_lines($kunjunganId);
    $subtotal = array_sum(array_column($lines, 'subtotal')) + $administrasi;
    $total = max(0, $subtotal - $diskon);

    try {
        db()->beginTransaction();
        if ($billing) {
            db()->prepare("UPDATE billing SET subtotal=?, diskon=?, total=?, status=? WHERE id=?")
              ->execute([$subtotal, $diskon, $total, $aksi === 'finalisasi' ? 'final' : 'draft', $billing['id']]);
            $billingId = (int) $billing['id'];
        } else {
            db()->prepare("INSERT INTO billing (kunjungan_id,subtotal,diskon,total,status) VALUES (?,?,?,?,?)")
              ->execute([$kunjunganId, $subtotal, $diskon, $total, $aksi === 'finalisasi' ? 'final' : 'draft']);
            $billingId = (int) db()->lastInsertId();
        }
        // rebuild detail
        db()->prepare("DELETE FROM billing_detail WHERE billing_id=?")->execute([$billingId]);
        $ins = db()->prepare("INSERT INTO billing_detail (billing_id,kategori,item_code,deskripsi,qty,tarif,subtotal) VALUES (?,?,?,?,?,?,?)");
        foreach ($lines as $l) {
            $ins->execute([$billingId, $l['kategori'], $l['item_code'] ?? '', $l['deskripsi'], $l['qty'], $l['tarif'], $l['subtotal']]);
        }
        if ($administrasi > 0) {
            $ins->execute([$billingId, 'administrasi', 'GBKAD0001', 'Biaya Administrasi', 1, $administrasi, $administrasi]);
        }

        if ($aksi === 'finalisasi') {
            db()->prepare("UPDATE kunjungan SET status='pembayaran' WHERE id=?")->execute([$kunjunganId]);
            db()->commit();
            set_flash('success', 'Billing difinalisasi. Total tagihan ' . rupiah($total) . '. Pasien diteruskan ke Pembayaran.');
            legacy_redirect('modules/billing/index.php');
        }
        db()->commit();
        set_flash('success', 'Billing disimpan sebagai draft.');
        legacy_redirect('modules/billing/proses.php?kunjungan_id=' . $kunjunganId);
    } catch (Throwable $ex) {
        if (db()->inTransaction()) db()->rollBack();
        $errors[] = 'Gagal menyimpan billing: ' . $ex->getMessage();
    }
}

// Data untuk tampilan: jika final, baca dari billing_detail tersimpan; jika belum, hitung live
if ($isFinal) {
    $s = db()->prepare("SELECT kategori,item_code,deskripsi,qty,tarif,subtotal FROM billing_detail WHERE billing_id=? ORDER BY id");
    $s->execute([$billing['id']]);
    $detailLines = $s->fetchAll();
    $subtotal = (float) $billing['subtotal'];
    $diskon = (float) $billing['diskon'];
    $total = (float) $billing['total'];
} else {
    $svc = collect_billing_lines($kunjunganId);
    $detailLines = $svc;
    if ($administrasi > 0) {
        $detailLines[] = ['kategori' => 'administrasi', 'item_code' => 'GBKAD0001', 'deskripsi' => 'Biaya Administrasi',
            'qty' => 1, 'tarif' => $administrasi, 'subtotal' => $administrasi];
    }
    $subtotal = array_sum(array_column($svc, 'subtotal')) + $administrasi;
    $total = max(0, $subtotal - $diskon);
}
$svcOnly = array_sum(array_column(array_filter($detailLines, fn($l) => $l['kategori'] !== 'administrasi'), 'subtotal'));

require_once __DIR__ . '/../../includes/header.php';
?>
<a href="<?= legacy_url('modules/billing/index.php') ?>" class="btn btn-light btn-sm"><?= app_icon("arrowleft") ?> Kembali</a>

<div class="card" style="margin-top:14px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:var(--fs-sub);font-weight:700"><?= e($kj['pasien']) ?></div>
    <div style="color:var(--muted)">No. MR <b><?= e($kj['no_mr']) ?></b> &middot; <?= e($kj['poli']) ?> &middot; <?= e($kj['dokter'] ?? '-') ?></div>
  </div>
  <div style="text-align:right">
    <div class="badge badge-blue">No. <?= e($kj['no_kunjungan']) ?></div>
    <?php if ($isFinal): ?><br><span class="badge badge-green" style="margin-top:6px">Billing Final</span><?php endif; ?>
  </div>
</div>

<?php if ($errors): ?><div class="alert alert-danger" style="margin-top:14px"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>
<?php if ($isFinal): ?><div class="alert alert-info" style="margin-top:14px">Billing sudah difinalisasi & terkunci. Lanjutkan ke <b>Keuangan</b> untuk pembayaran.</div><?php endif; ?>

<div class="section-title">Rincian Layanan</div>
<div class="table-wrap">
  <table style="width:100%">
    <thead><tr><th>Kategori</th><th style="width:110px">Kode</th><th>Deskripsi</th><th style="width:70px">Qty</th>
      <th style="width:140px;text-align:right">Tarif</th><th style="width:150px;text-align:right">Subtotal</th></tr></thead>
    <tbody>
      <?php if (!$detailLines): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:20px">Belum ada layanan tercatat untuk kunjungan ini.</td></tr>
      <?php else: foreach ($detailLines as $l): ?>
        <tr>
          <td><span class="badge badge-gray"><?= e(billing_kategori_label($l['kategori'])) ?></span></td>
          <td><code><?= e($l['item_code'] ?? '') ?></code></td>
          <td><?= e($l['deskripsi']) ?></td>
          <td><?= (int) $l['qty'] ?></td>
          <td style="text-align:right"><?= rupiah($l['tarif']) ?></td>
          <td style="text-align:right"><?= rupiah($l['subtotal']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<form method="post" style="margin-top:18px">
  <?= sim_csrf_field() ?>
  <input type="hidden" name="kunjungan_id" value="<?= (int) $kunjunganId ?>">
  <input type="hidden" name="aksi" id="aksi" value="simpan">
  <div class="card" style="max-width:460px;margin-left:auto">
    <div style="display:flex;justify-content:space-between;padding:6px 0">
      <span>Subtotal Layanan</span><b><?= rupiah($svcOnly) ?></b>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0">
      <label style="margin:0">Biaya Administrasi</label>
      <input type="number" min="0" step="any" name="administrasi" id="administrasi" class="form-control"
             style="width:160px;text-align:right" value="<?= (int) $administrasi ?>" <?= $isFinal ? 'disabled' : '' ?> oninput="hitung()">
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0">
      <label style="margin:0">Diskon</label>
      <input type="number" min="0" step="any" name="diskon" id="diskon" class="form-control"
             style="width:160px;text-align:right" value="<?= (int) $diskon ?>" <?= $isFinal ? 'disabled' : '' ?> oninput="hitung()">
    </div>
    <hr style="border:none;border-top:1px solid var(--border);margin:8px 0">
    <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:var(--fs-sub);font-weight:700">
      <span>TOTAL TAGIHAN</span><span id="totalView"><?= rupiah($total) ?></span>
    </div>
    <?php if (!$isFinal): ?>
    <div style="display:flex;gap:10px;margin-top:14px">
      <button type="submit" class="btn btn-light" onclick="document.getElementById('aksi').value='simpan'"><?= app_icon("save") ?> Simpan Draft</button>
      <button type="submit" class="btn btn-green" onclick="document.getElementById('aksi').value='finalisasi'"><?= app_icon("check") ?> Finalisasi Billing</button>
    </div>
    <?php else: ?>
      <a class="btn" style="margin-top:14px;width:100%;justify-content:center" href="<?= legacy_url('modules/keuangan/index.php') ?>"><?= app_icon("keuangan") ?> Lanjut ke Pembayaran</a>
    <?php endif; ?>
  </div>
</form>

<script>
var svcTotal = <?= (float) $svcOnly ?>;
function fmt(n){ return 'Rp ' + (n<0?0:n).toLocaleString('id-ID'); }
function hitung(){
  var adm = parseFloat(document.getElementById('administrasi').value)||0;
  var dis = parseFloat(document.getElementById('diskon').value)||0;
  document.getElementById('totalView').textContent = fmt(svcTotal + adm - dis);
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
