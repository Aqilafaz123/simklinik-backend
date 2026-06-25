<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('registrasi', 'admin', 'superadmin');
require_once __DIR__ . '/../../includes/icons.php'; // agar app_icon() tersedia di mode modal

$modal = isset($_GET['modal']);
$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    "SELECT p.*, kp.nama AS kelompok FROM pasien p
     LEFT JOIN kelompok_pasien kp ON kp.id = p.kelompok_id
     WHERE p.id = ?"
);
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    if ($modal) { echo '<div class="alert alert-danger">Pasien tidak ditemukan.</div>'; exit; }
    set_flash('danger', 'Pasien tidak ditemukan.');
    legacy_redirect('modules/registrasi/pasien.php');
}

// Hitung umur dari tanggal lahir (format senada rekam medis)
$umur = $p['tgl_lahir'] ? (int) ((time() - strtotime($p['tgl_lahir'])) / 31556952) . ' tahun' : '-';

// Seksi tampilan — cermin dari pasien_form.php (label & pengelompokan)
$sections = [
    ['Identitas Pasien', 'user', 'acc-blue', [
        'nik'             => 'NIK',
        'no_kk'           => 'No. Kartu Keluarga',
        'nama_ibu'        => 'Nama Ibu Kandung',
        'tempat_lahir'    => 'Tempat Lahir',
        'tgl_lahir'       => 'Tanggal Lahir',
        'jenis_kelamin'   => 'Jenis Kelamin',
        'gol_darah'       => 'Golongan Darah',
        'agama'           => 'Agama',
        'status_kawin'    => 'Status Perkawinan',
        'pendidikan'      => 'Pendidikan',
        'kewarganegaraan' => 'Kewarganegaraan',
    ]],
    ['Alamat & Kontak', 'idcard', 'acc-green', [
        'alamat'    => ['Alamat', 'full'],
        'rt_rw'     => 'RT / RW',
        'kode_pos'  => 'Kode Pos',
        'kelurahan' => 'Kelurahan / Desa',
        'kecamatan' => 'Kecamatan',
        'kota'      => 'Kota / Kabupaten',
        'provinsi'  => 'Provinsi',
        'telepon'   => 'Telepon / HP',
        'email'     => 'Email',
    ]],
    ['Penjamin & Pekerjaan', 'shield', 'acc-orange', [
        'kelompok'    => 'Kelompok Pasien',
        'no_asuransi' => 'No. Asuransi',
        'pekerjaan'   => 'Pekerjaan',
    ]],
    ['Kontak Darurat', 'users', 'acc-purple', [
        'kontak_nama'     => 'Nama Kontak Darurat',
        'kontak_hubungan' => 'Hubungan',
        'kontak_telepon'  => 'Telepon Kontak',
    ]],
    ['Informasi Medis', 'pills', 'acc-red', [
        'alergi'           => ['Riwayat Alergi', 'full'],
        'riwayat_penyakit' => ['Riwayat Penyakit', 'full'],
    ]],
];

// Format satu nilai menjadi string siap-tampil
$fmt = function (string $key, $val): string {
    if ($key === 'jenis_kelamin') return $val === 'L' ? 'Laki-laki' : ($val === 'P' ? 'Perempuan' : '');
    if ($key === 'tgl_lahir') return $val ? tgl_id($val) : '';
    if ($key === 'gol_darah') return ($val && $val !== '-') ? $val : '';
    return (string) $val;
};

ob_start();
?>
<!-- Identitas ringkas — model senada rekam medis -->
<div class="card detail-id">
  <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <div style="font-size:var(--fs-title);font-weight:700"><?= e($p['nama']) ?></div>
      <div style="color:var(--muted);margin-top:4px">
        No. MR <b><?= e($p['no_mr']) ?></b> &middot; <?= $p['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
        &middot; <?= e($umur) ?> &middot; <?= e($p['kelompok'] ?? 'Umum') ?>
      </div>
      <div style="color:var(--muted)">
        <?= $p['tgl_lahir'] ? tgl_id($p['tgl_lahir']) : '-' ?> &middot; <?= e($p['telepon'] ?? '-') ?>
        <?php if ($p['gol_darah'] && $p['gol_darah'] !== '-'): ?> &middot; Gol. Darah <?= e($p['gol_darah']) ?><?php endif; ?>
      </div>
      <?php if (!empty($p['alamat'])): ?><div style="color:var(--muted)"><?= e($p['alamat']) ?></div><?php endif; ?>
    </div>
    <div style="text-align:right">
      <?php if (!empty($p['alergi'])): ?><span class="badge badge-red"><?= app_icon('alert') ?> Alergi: <?= e($p['alergi']) ?></span><?php endif; ?>
    </div>
  </div>
</div>

<?php foreach ($sections as [$title, $icon, $acc, $flds]): ?>
  <div class="detail-section">
    <div class="step-head">
      <div class="step-num <?= e($acc) ?>"><?= app_icon($icon) ?></div>
      <div><div class="st-title"><?= e($title) ?></div></div>
    </div>
    <div class="detail-grid">
      <?php foreach ($flds as $key => $meta):
        $label = is_array($meta) ? $meta[0] : $meta;
        $full  = is_array($meta) && ($meta[1] ?? '') === 'full';
        $value = $fmt($key, $p[$key] ?? '');
        $empty = trim($value) === '';
      ?>
        <div class="detail-item<?= $full ? ' dg-full' : '' ?>">
          <div class="di-label"><?= e($label) ?></div>
          <div class="di-value<?= $empty ? ' empty' : '' ?>"><?= $empty ? '—' : e($value) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>

<div class="form-actions" style="margin-top:6px">
  <?php if ($modal): ?>
    <button type="button" class="btn btn-light" data-modal-close>Tutup</button>
  <?php endif; ?>
  <a class="btn" href="<?= legacy_url('modules/registrasi/pasien_form.php?id=' . $p['id']) ?>"><?= app_icon('pencil') ?> Edit Data</a>
</div>
<?php
$body = ob_get_clean();

if ($modal) { echo $body; exit; }

// Mode halaman penuh
$pageTitle = 'Detail Pasien';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title">Detail Pasien</div>
    <div class="pt-sub">No. MR: <b><?= e($p['no_mr']) ?></b></div>
  </div>
  <div class="pt-actions">
    <a class="btn-back" href="<?= legacy_url('modules/registrasi/pasien.php') ?>"><?= app_icon('chevron') ?> Kembali</a>
  </div>
</div>
<div class="card"><?= $body ?></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
