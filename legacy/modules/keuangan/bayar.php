<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/keuangan_lib.php';
require_role('kasir', 'admin', 'superadmin');
$pageTitle = 'Pembayaran';
$user = current_user();

$kunjunganId = (int) ($_GET['kunjungan_id'] ?? $_POST['kunjungan_id'] ?? 0);

$kj = db()->prepare(
    "SELECT k.*, p.no_mr, p.nama AS pasien, po.nama AS poli,
            a.nama AS asuransi_nama, c.nama AS corporate_nama, c.limit_jaminan
     FROM kunjungan k
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN asuransi a ON a.id = k.asuransi_id
     LEFT JOIN corporate c ON c.id = k.corporate_id
     WHERE k.id = ?");
$kj->execute([$kunjunganId]);
$kj = $kj->fetch();
if (!$kj) { set_flash('danger', 'Kunjungan tidak ditemukan.'); legacy_redirect('modules/keuangan/index.php'); }

$invoice = get_or_create_invoice($kunjunganId);
if (!$invoice) { set_flash('danger', 'Billing belum difinalisasi. Selesaikan billing dulu.'); legacy_redirect('modules/billing/proses.php?kunjungan_id=' . $kunjunganId); }

$banks = db()->query("SELECT id,nama_bank,no_rekening,atas_nama FROM bank WHERE status='aktif' ORDER BY nama_bank")->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'bayar') {
    sim_csrf_verify();
    $metode = $_POST['metode'] ?? 'cash';
    $jumlah = (float) ($_POST['jumlah'] ?? 0);
    $bankId = ($metode === 'transfer') ? ((int) ($_POST['bank_id'] ?? 0) ?: null) : null;
    $ket    = trim($_POST['keterangan'] ?? '') ?: null;
    $sisa   = (float) $invoice['total'] - (float) $invoice['terbayar'];

    $allowed = ['cash','transfer','qris','edc','va','ewallet','penjamin'];
    if (!in_array($metode, $allowed, true)) $errors[] = 'Metode pembayaran tidak valid.';
    if ($jumlah <= 0) $errors[] = 'Jumlah pembayaran harus lebih dari 0.';
    if ($jumlah > $sisa + 0.01) $errors[] = 'Jumlah melebihi sisa tagihan (' . rupiah($sisa) . ').';
    if ($invoice['status'] === 'lunas') $errors[] = 'Invoice sudah lunas.';

    // Upload bukti (untuk metode non-tunai)
    $buktiPath = null;
    if (!$errors && !empty($_FILES['bukti']['name']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['bukti'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $okExt = ['jpg','jpeg','png','pdf'];
        if (!in_array($ext, $okExt, true)) {
            $errors[] = 'Bukti harus berupa JPG, PNG, atau PDF.';
        } elseif ($f['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran bukti maksimal 2 MB.';
        } else {
            $fname = 'bukti_' . $invoice['id'] . '_' . time() . '.' . $ext;
            $dest = UPLOAD_PATH . '/bukti/' . $fname;
            if (move_uploaded_file($f['tmp_name'], $dest)) {
                $buktiPath = 'uploads/bukti/' . $fname;
            } else {
                $errors[] = 'Gagal mengunggah bukti.';
            }
        }
    }

    if (!$errors) {
        try {
            db()->beginTransaction();
            db()->prepare(
                "INSERT INTO pembayaran (invoice_id,metode,bank_id,jumlah,bukti,status,keterangan,user_id)
                 VALUES (?,?,?,?,?, 'valid', ?, ?)")
              ->execute([$invoice['id'], $metode, $bankId, $jumlah, $buktiPath, $ket, $user['id']]);
            $status = recompute_invoice((int) $invoice['id']);
            db()->commit();
            set_flash('success', $status === 'lunas'
                ? 'Pembayaran lunas. Kunjungan selesai. Silakan cetak struk.'
                : 'Pembayaran tercatat. Sisa tagihan ' . rupiah($sisa - $jumlah) . '.');
            legacy_redirect('modules/keuangan/bayar.php?kunjungan_id=' . $kunjunganId);
        } catch (Throwable $ex) {
            if (db()->inTransaction()) db()->rollBack();
            $errors[] = 'Gagal menyimpan pembayaran: ' . $ex->getMessage();
        }
    }
}

// refresh invoice setelah kemungkinan perubahan
$inv = db()->prepare("SELECT * FROM invoice WHERE id=?"); $inv->execute([$invoice['id']]); $invoice = $inv->fetch();
$sisa = (float) $invoice['total'] - (float) $invoice['terbayar'];
$isLunas = $invoice['status'] === 'lunas';

$pmts = db()->prepare(
    "SELECT pm.*, b.nama_bank, u.nama AS kasir FROM pembayaran pm
     LEFT JOIN bank b ON b.id = pm.bank_id
     LEFT JOIN users u ON u.id = pm.user_id
     WHERE pm.invoice_id=? ORDER BY pm.id");
$pmts->execute([$invoice['id']]);
$pmts = $pmts->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<a href="<?= legacy_url('modules/keuangan/index.php') ?>" class="btn btn-light btn-sm"><?= app_icon("arrowleft") ?> Kembali</a>

<div class="pay-page">

  <!-- Hero: identitas pasien & invoice -->
  <div class="bd-hero">
    <div class="bd-hero-glow"></div>
    <div class="bd-hero-main">
      <div class="bd-avatar"><?= app_icon('idcard') ?></div>
      <div>
        <div class="bd-pasien"><?= e($kj['pasien']) ?></div>
        <div class="bd-meta">
          <span><?= app_icon('user') ?> No. MR <b><?= e($kj['no_mr']) ?></b></span>
          <span><?= app_icon('hospital') ?> <?= e($kj['poli']) ?></span>
          <span><?= app_icon('ticket') ?> <?= e($kj['no_kunjungan']) ?></span>
        </div>
      </div>
    </div>
    <div class="bd-hero-side">
      <div class="bd-kunjungan">Invoice <?= e($invoice['no_invoice']) ?></div>
      <span class="badge <?= $isLunas ? 'badge-green' : 'badge-orange' ?>"><?= $isLunas ? 'LUNAS' : 'Belum Lunas' ?></span>
    </div>
  </div>

  <!-- Verifikasi Penjamin -->
  <div class="pay-penjamin">
    <?php if ($kj['jenis_penjamin'] === 'umum'): ?>
      <span class="badge badge-gray">UMUM</span> <span>Pasien membayar sendiri (tanpa penjamin).</span>
    <?php else: ?>
      <span class="badge badge-blue"><?= e(strtoupper($kj['jenis_penjamin'])) ?></span>
      <?php if ($kj['asuransi_nama']): ?><span>&middot; <b><?= e($kj['asuransi_nama']) ?></b></span><?php endif; ?>
      <?php if ($kj['corporate_nama']): ?><span>&middot; <b><?= e($kj['corporate_nama']) ?></b> (limit <?= rupiah($kj['limit_jaminan']) ?>)</span><?php endif; ?>
      <?php if ($kj['no_jaminan']): ?><span>&middot; No. Jaminan: <?= e($kj['no_jaminan']) ?></span><?php endif; ?>
      <p class="note">Catat tanggungan penjamin dengan metode <b>Penjamin</b> di form, sisanya dibayar pasien.</p>
    <?php endif; ?>
  </div>

  <?php if ($errors): ?><div class="alert alert-danger"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

  <div class="pay-grid">
    <!-- Ringkasan tagihan -->
    <div class="pay-card">
      <div class="pay-card-title"><?= app_icon('billing') ?> Ringkasan Tagihan</div>
      <div class="pay-sum-rows">
        <div class="bd-sum-row"><span>Total Tagihan</span><b><?= rupiah($invoice['total']) ?></b></div>
        <div class="bd-sum-row"><span>Sudah Dibayar</span><b style="color:var(--green)"><?= rupiah($invoice['terbayar']) ?></b></div>
      </div>
      <div class="pay-sisa <?= $sisa > 0 ? 'owe' : 'paid' ?>">
        <div class="lbl">Sisa Tagihan</div>
        <div class="val"><?= rupiah($sisa) ?></div>
      </div>
      <?php if ($isLunas): ?>
        <a class="btn btn-green" target="_blank" style="margin-top:14px;width:100%;justify-content:center" href="<?= legacy_url('modules/keuangan/struk.php?invoice_id=' . $invoice['id']) ?>"><?= app_icon("print") ?> Cetak Struk</a>
      <?php endif; ?>
    </div>

    <!-- Form pembayaran -->
    <div class="pay-card">
      <div class="pay-card-title"><?= app_icon('money') ?> Input Pembayaran</div>
      <?php if ($isLunas): ?>
        <div class="pay-lunas-banner">
          <?= app_icon('check') ?>
          <div><b>Tagihan sudah lunas</b><small>Tidak ada pembayaran lagi. Silakan cetak struk.</small></div>
        </div>
      <?php else: ?>
      <form method="post" enctype="multipart/form-data">
        <?= sim_csrf_field() ?>
        <input type="hidden" name="form" value="bayar">
        <input type="hidden" name="kunjungan_id" value="<?= (int) $kunjunganId ?>">
        <div class="form-group">
          <label>Metode Pembayaran</label>
          <select name="metode" id="metode" class="form-control" onchange="toggleMetode()">
            <option value="cash">Tunai (Cash)</option>
            <option value="transfer">Transfer Bank</option>
            <option value="qris">QRIS</option>
            <option value="edc">EDC / Debit</option>
            <option value="va">Virtual Account</option>
            <option value="ewallet">E-Wallet</option>
            <?php if ($kj['jenis_penjamin'] !== 'umum'): ?><option value="penjamin">Penjamin (Asuransi/BPJS/Corporate)</option><?php endif; ?>
          </select>
        </div>
        <div class="form-group" id="boxBank" style="display:none">
          <label>Bank Tujuan</label>
          <select name="bank_id" class="form-control">
            <option value="">- Pilih -</option>
            <?php foreach ($banks as $b): ?>
              <option value="<?= $b['id'] ?>"><?= e($b['nama_bank']) ?> — <?= e($b['no_rekening']) ?> (<?= e($b['atas_nama']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Jumlah Bayar</label>
          <input type="number" min="0" step="any" name="jumlah" class="form-control" value="<?= (int) $sisa ?>">
        </div>
        <div class="form-group" id="boxBukti" style="display:none">
          <label>Upload Bukti (JPG/PNG/PDF, maks 2MB)</label>
          <input type="file" name="bukti" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
        </div>
        <div class="form-group">
          <label>Keterangan</label>
          <input type="text" name="keterangan" class="form-control">
        </div>
        <button type="submit" class="btn btn-green" style="width:100%;justify-content:center"><?= app_icon("check") ?> Simpan Pembayaran</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Riwayat pembayaran -->
  <div class="pay-card">
    <div class="pay-card-title"><?= app_icon('clock') ?> Riwayat Pembayaran</div>
    <div class="bd-table-wrap">
      <table class="bd-table">
        <thead><tr><th>Waktu</th><th>Metode</th><th>Bank</th><th class="num">Jumlah</th><th>Bukti</th><th>Kasir</th></tr></thead>
        <tbody>
          <?php if (!$pmts): ?>
            <tr><td colspan="6" class="bd-empty">Belum ada pembayaran.</td></tr>
          <?php else: foreach ($pmts as $pm): ?>
            <tr>
              <td><?= tgl_id($pm['tanggal'], true) ?></td>
              <td><span class="badge badge-gray"><?= e(metode_label($pm['metode'])) ?></span></td>
              <td><?= e($pm['nama_bank'] ?? '-') ?></td>
              <td class="num bold"><?= rupiah($pm['jumlah']) ?></td>
              <td><?= $pm['bukti'] ? '<a target="_blank" href="' . legacy_url($pm['bukti']) . '">lihat</a>' : '-' ?></td>
              <td><?= e($pm['kasir'] ?? '-') ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function toggleMetode(){
  var m = document.getElementById('metode').value;
  document.getElementById('boxBank').style.display = (m === 'transfer') ? '' : 'none';
  // bukti untuk semua non-tunai & non-penjamin
  document.getElementById('boxBukti').style.display = (m !== 'cash' && m !== 'penjamin') ? '' : 'none';
}
toggleMetode();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
