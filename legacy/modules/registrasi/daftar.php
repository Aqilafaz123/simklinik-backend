<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('registrasi', 'admin', 'superadmin');
$pageTitle = 'Daftar Kunjungan';

$user = current_user();
$poli      = db()->query("SELECT id, kode, nama FROM poli WHERE status='aktif' ORDER BY nama")->fetchAll();
$dokter    = db()->query("SELECT id, nama, poli_id FROM dokter WHERE status='aktif' ORDER BY nama")->fetchAll();
$asuransi  = db()->query("SELECT id, nama FROM asuransi WHERE status='aktif' ORDER BY nama")->fetchAll();
$corporate = db()->query("SELECT id, nama FROM corporate WHERE status='aktif' ORDER BY nama")->fetchAll();

// Pasien terpilih (dari ?pasien_id atau hasil submit)
$pasienId = (int) ($_GET['pasien_id'] ?? $_POST['pasien_id'] ?? 0);
$pasien = null;
if ($pasienId) {
    $stmt = db()->prepare("SELECT * FROM pasien WHERE id = ?");
    $stmt->execute([$pasienId]);
    $pasien = $stmt->fetch() ?: null;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sim_csrf_verify();
    $poliId   = (int) ($_POST['poli_id'] ?? 0);
    $dokterId = (int) ($_POST['dokter_id'] ?? 0) ?: null;
    $tgl      = $_POST['tgl_kunjungan'] ?? date('Y-m-d');
    $jenis    = $_POST['jenis_penjamin'] ?? 'umum';
    $keluhan  = trim($_POST['keluhan_awal'] ?? '');
    $asuransiId  = $jenis === 'asuransi' || $jenis === 'bpjs' ? ((int) ($_POST['asuransi_id'] ?? 0) ?: null) : null;
    $corporateId = $jenis === 'corporate' ? ((int) ($_POST['corporate_id'] ?? 0) ?: null) : null;
    $noJaminan   = trim($_POST['no_jaminan'] ?? '') ?: null;

    if (!$pasien)  $errors[] = 'Pasien belum dipilih.';
    if (!$poliId)  $errors[] = 'Poli tujuan wajib dipilih.';
    if (!in_array($jenis, ['umum', 'bpjs', 'asuransi', 'corporate'], true)) $errors[] = 'Jenis penjamin tidak valid.';

    if (!$errors) {
        try {
            db()->beginTransaction();
            // nomor antrian per poli per hari
            $stmt = db()->prepare(
                "SELECT COALESCE(MAX(no_antrian),0)+1 FROM kunjungan WHERE poli_id=? AND tgl_kunjungan=?");
            $stmt->execute([$poliId, $tgl]);
            $noAntrian = (int) $stmt->fetchColumn();
            $noKunjungan = generate_no('KJ', 'kunjungan', 'no_kunjungan');

            db()->prepare(
                "INSERT INTO kunjungan
                 (no_kunjungan, pasien_id, poli_id, dokter_id, tgl_kunjungan, no_antrian,
                  jenis_penjamin, asuransi_id, corporate_id, no_jaminan, status, keluhan_awal, user_id)
                 VALUES (?,?,?,?,?,?,?,?,?,?, 'menunggu', ?, ?)")
              ->execute([$noKunjungan, $pasien['id'], $poliId, $dokterId, $tgl, $noAntrian,
                  $jenis, $asuransiId, $corporateId, $noJaminan, $keluhan, $user['id']]);
            $kunjunganId = (int) db()->lastInsertId();
            db()->commit();

            set_flash('success', "Kunjungan $noKunjungan terdaftar. Nomor antrian: " .
                str_pad((string) $noAntrian, 3, '0', STR_PAD_LEFT));
            legacy_redirect('modules/registrasi/cetak_antrian.php?id=' . $kunjunganId);
        } catch (Throwable $ex) {
            if (db()->inTransaction()) db()->rollBack();
            $errors[] = 'Gagal menyimpan kunjungan: ' . $ex->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title">Pendaftaran Kunjungan</div>
    <div class="pt-sub">Pilih pasien, lalu lengkapi tujuan poli &amp; penjamin</div>
  </div>
  <div class="pt-actions">
    <a class="btn-back" href="<?= legacy_url('modules/registrasi/index.php') ?>"><?= app_icon('chevron') ?> Kembali ke Daftar</a>
  </div>
</div>

<div class="form-wizard">

  <!-- KIRI: pilih pasien -->
  <div class="card">
    <div class="step-head">
      <div class="step-num">1</div>
      <div><div class="st-title">Pilih Pasien</div><div class="st-sub">Cari pasien lama atau daftar baru</div></div>
    </div>
    <?php if ($pasien): ?>
      <div class="patient-box">
        <div class="pname">
          <span class="av"><?= strtoupper(substr($pasien['nama'], 0, 1)) ?></span>
          <?= e($pasien['nama']) ?>
        </div>
        <div class="patient-meta">
          No. MR: <b><?= e($pasien['no_mr']) ?></b><br>
          <?= $pasien['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?> &middot;
          <?= tgl_id($pasien['tgl_lahir']) ?><br>
          Telepon: <?= e($pasien['telepon'] ?? '-') ?>
          <?php if (!empty($pasien['alergi'])): ?>
            <br><span class="badge badge-red">Alergi: <?= e($pasien['alergi']) ?></span>
          <?php endif; ?>
        </div>
        <a class="btn btn-sm btn-light" style="margin-top:12px" href="<?= legacy_url('modules/registrasi/daftar.php') ?>"><?= app_icon('search') ?> Ganti Pasien</a>
      </div>
    <?php else: ?>
      <form method="get" class="search-inline">
        <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" class="form-control" placeholder="Cari nama / No. MR / NIK..." autofocus>
        <button class="btn" type="submit"><?= app_icon('search') ?></button>
      </form>
      <?php
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $s = db()->prepare("SELECT id, no_mr, nama, telepon FROM pasien
                 WHERE no_mr LIKE ? OR nama LIKE ? OR nik LIKE ? ORDER BY nama LIMIT 20");
            $like = "%$q%"; $s->execute([$like, $like, $like]);
            $hasil = $s->fetchAll();
            if (!$hasil) echo '<p class="result-empty">Pasien tidak ditemukan. Coba kata kunci lain atau daftarkan baru.</p>';
            foreach ($hasil as $h) {
                echo '<a class="result-item" href="' . legacy_url('modules/registrasi/daftar.php?pasien_id=' . $h['id']) . '">'
                   . '<span class="av">' . strtoupper(substr($h['nama'], 0, 1)) . '</span>'
                   . '<span><span class="ri-name">' . e($h['nama']) . '</span>'
                   . '<span class="ri-meta">' . e($h['no_mr']) . ' &middot; ' . e($h['telepon'] ?? '-') . '</span></span></a>';
            }
        } else {
            echo '<p class="result-empty">Ketik nama, No. MR, atau NIK untuk mencari pasien lama.</p>';
        }
      ?>
      <a class="btn btn-green btn-block" style="margin-top:8px" href="<?= legacy_url('modules/registrasi/pasien_form.php') ?>"><?= app_icon('plus') ?> Daftar Pasien Baru</a>
    <?php endif; ?>
  </div>

  <!-- KANAN: detail kunjungan -->
  <div class="card">
    <div class="step-head">
      <div class="step-num">2</div>
      <div><div class="st-title">Poli, Dokter &amp; Penjamin</div><div class="st-sub">Detail kunjungan pasien</div></div>
    </div>
    <?php if ($errors): ?>
      <div class="alert alert-danger"><?= implode('<br>', array_map('e', $errors)) ?></div>
    <?php endif; ?>
    <form method="post" <?= $pasien ? '' : 'style="opacity:.5;pointer-events:none"' ?>>
      <?= sim_csrf_field() ?>
      <input type="hidden" name="pasien_id" value="<?= (int) ($pasien['id'] ?? 0) ?>">
      <div class="form-row">
        <div class="form-group">
          <label>Tanggal Kunjungan</label>
          <input type="date" name="tgl_kunjungan" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label>Poli Tujuan *</label>
          <select name="poli_id" id="poliSelect" class="form-control" required onchange="filterDokter()">
            <option value="">- Pilih Poli -</option>
            <?php foreach ($poli as $po): ?>
              <option value="<?= $po['id'] ?>"><?= e($po['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Dokter</label>
        <select name="dokter_id" id="dokterSelect" class="form-control">
          <option value="">- Pilih Dokter (opsional) -</option>
          <?php foreach ($dokter as $d): ?>
            <option value="<?= $d['id'] ?>" data-poli="<?= (int) $d['poli_id'] ?>"><?= e($d['nama']) ?></option>
          <?php endforeach; ?>
        </select>
        <small id="dokterHint" style="color:var(--muted);font-size:12.5px;display:none">Pilih poli dahulu untuk menyaring dokter.</small>
      </div>
      <div class="form-group">
        <label>Jenis Penjamin</label>
        <select name="jenis_penjamin" id="jenis_penjamin" class="form-control" onchange="togglePenjamin()">
          <option value="umum">Umum</option>
          <option value="bpjs">BPJS</option>
          <option value="asuransi">Asuransi Swasta</option>
          <option value="corporate">Corporate / Perusahaan</option>
        </select>
      </div>
      <div class="form-group penjamin-extra" id="box_asuransi" style="display:none">
        <label>Asuransi</label>
        <select name="asuransi_id" class="form-control">
          <option value="">- Pilih -</option>
          <?php foreach ($asuransi as $a): ?>
            <option value="<?= $a['id'] ?>"><?= e($a['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group penjamin-extra" id="box_corporate" style="display:none">
        <label>Perusahaan</label>
        <select name="corporate_id" class="form-control">
          <option value="">- Pilih -</option>
          <?php foreach ($corporate as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group penjamin-extra" id="box_nojaminan" style="display:none">
        <label>No. Kartu / Jaminan</label>
        <input type="text" name="no_jaminan" class="form-control">
      </div>
      <div class="form-group">
        <label>Keluhan Awal</label>
        <textarea name="keluhan_awal" class="form-control" rows="2" placeholder="Keluhan utama pasien..."></textarea>
      </div>
      <button class="btn btn-block" type="submit"><?= app_icon('ticket') ?> Daftarkan &amp; Buat Antrian</button>
    </form>
  </div>
</div>

<script>
function togglePenjamin() {
  var v = document.getElementById('jenis_penjamin').value;
  document.querySelectorAll('.penjamin-extra').forEach(function(el){ el.style.display='none'; });
  if (v === 'asuransi' || v === 'bpjs') {
    document.getElementById('box_asuransi').style.display = '';
    document.getElementById('box_nojaminan').style.display = '';
  } else if (v === 'corporate') {
    document.getElementById('box_corporate').style.display = '';
    document.getElementById('box_nojaminan').style.display = '';
  }
}

// Saring daftar dokter sesuai poli terpilih. Simpan daftar lengkap sekali,
// lalu bangun ulang opsi (lebih andal daripada menyembunyikan <option>).
var _dokterAll = (function () {
  var sel = document.getElementById('dokterSelect'), arr = [];
  Array.prototype.forEach.call(sel.options, function (o) {
    if (o.value) arr.push({ id: o.value, nama: o.text, poli: o.getAttribute('data-poli') });
  });
  return arr;
})();
function filterDokter() {
  var poli = document.getElementById('poliSelect').value;
  var sel  = document.getElementById('dokterSelect');
  var hint = document.getElementById('dokterHint');
  var prev = sel.value;
  sel.innerHTML = '<option value="">- Pilih Dokter (opsional) -</option>';
  var list = poli ? _dokterAll.filter(function (d) { return d.poli === poli; }) : _dokterAll;
  list.forEach(function (d) {
    var o = document.createElement('option');
    o.value = d.id; o.text = d.nama; o.setAttribute('data-poli', d.poli);
    if (d.id === prev) o.selected = true;
    sel.appendChild(o);
  });
  // Bila hanya satu dokter di poli itu, langsung pilih.
  if (poli && list.length === 1) sel.value = list[0].id;
  hint.style.display = (poli && list.length === 0) ? '' : 'none';
  if (poli && list.length === 0) hint.textContent = 'Belum ada dokter terdaftar untuk poli ini.';
}
document.addEventListener('DOMContentLoaded', filterDokter);
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
