<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('registrasi', 'admin', 'superadmin');

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$pageTitle = $isEdit ? t('pages.patient_edit') : t('pages.patient_new');

$kelompok = db()->query("SELECT id, nama FROM kelompok_pasien ORDER BY id")->fetchAll();

/*
 * Definisi field pasien — dikelompokkan per seksi.
 * Untuk MENAMBAH data pasien baru nanti: tambah kolom di tabel `pasien`
 * (lihat database/migrasi_pasien_lengkap.sql) lalu tambah satu baris di sini.
 *
 * type : text | date | email | tel | number | textarea | select | fk
 * span : 'full' untuk membentang 2 kolom (default setengah)
 */
$opt = fn(array $list) => array_combine($list, $list); // value = label
$sections = [
    ['Identitas Pasien', 'user', 'acc-blue', [
        'nama'            => ['label' => 'Nama Lengkap', 'type' => 'text', 'required' => true, 'span' => 'full', 'autofocus' => true],
        'nik'             => ['label' => 'NIK', 'type' => 'text', 'inputmode' => 'numeric'],
        'no_kk'           => ['label' => 'No. Kartu Keluarga', 'type' => 'text', 'inputmode' => 'numeric'],
        'nama_ibu'        => ['label' => 'Nama Ibu Kandung', 'type' => 'text'],
        'tempat_lahir'    => ['label' => 'Tempat Lahir', 'type' => 'text'],
        'tgl_lahir'       => ['label' => 'Tanggal Lahir', 'type' => 'date'],
        'jenis_kelamin'   => ['label' => 'Jenis Kelamin', 'type' => 'select', 'required' => true,
                              'options' => ['L' => 'Laki-laki', 'P' => 'Perempuan'], 'default' => 'L'],
        'gol_darah'       => ['label' => 'Golongan Darah', 'type' => 'select',
                              'options' => ['-' => '-', 'A' => 'A', 'B' => 'B', 'AB' => 'AB', 'O' => 'O'], 'default' => '-'],
        'agama'           => ['label' => 'Agama', 'type' => 'select',
                              'options' => $opt(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'])],
        'status_kawin'    => ['label' => 'Status Perkawinan', 'type' => 'select',
                              'options' => $opt(['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'])],
        'pendidikan'      => ['label' => 'Pendidikan', 'type' => 'select',
                              'options' => $opt(['Tidak Sekolah', 'SD', 'SMP', 'SMA/SMK', 'D1/D2/D3', 'S1', 'S2', 'S3'])],
        'kewarganegaraan' => ['label' => 'Kewarganegaraan', 'type' => 'select',
                              'options' => ['WNI' => 'WNI', 'WNA' => 'WNA'], 'default' => 'WNI'],
    ]],
    ['Alamat & Kontak', 'idcard', 'acc-green', [
        'alamat'    => ['label' => 'Alamat (Jalan/Dusun)', 'type' => 'textarea', 'span' => 'full'],
        'rt_rw'     => ['label' => 'RT / RW', 'type' => 'text', 'placeholder' => 'cth: 001/002'],
        'kode_pos'  => ['label' => 'Kode Pos', 'type' => 'text', 'inputmode' => 'numeric'],
        'kelurahan' => ['label' => 'Kelurahan / Desa', 'type' => 'text'],
        'kecamatan' => ['label' => 'Kecamatan', 'type' => 'text'],
        'kota'      => ['label' => 'Kota / Kabupaten', 'type' => 'text'],
        'provinsi'  => ['label' => 'Provinsi', 'type' => 'text'],
        'telepon'   => ['label' => 'Telepon / HP', 'type' => 'tel'],
        'email'     => ['label' => 'Email', 'type' => 'email'],
    ]],
    ['Penjamin & Pekerjaan', 'shield', 'acc-orange', [
        'kelompok_id' => ['label' => 'Kelompok Pasien', 'type' => 'fk'],
        'no_asuransi' => ['label' => 'No. Asuransi', 'type' => 'text'],
        'pekerjaan'   => ['label' => 'Pekerjaan', 'type' => 'text'],
    ]],
    ['Kontak Darurat', 'users', 'acc-purple', [
        'kontak_nama'     => ['label' => 'Nama Kontak Darurat', 'type' => 'text'],
        'kontak_hubungan' => ['label' => 'Hubungan', 'type' => 'text', 'placeholder' => 'cth: Suami/Istri/Anak'],
        'kontak_telepon'  => ['label' => 'Telepon Kontak', 'type' => 'tel'],
    ]],
    ['Informasi Medis', 'pills', 'acc-red', [
        'alergi'           => ['label' => 'Riwayat Alergi', 'type' => 'text', 'span' => 'full', 'placeholder' => 'cth: Penisilin, Seafood'],
        'riwayat_penyakit' => ['label' => 'Riwayat Penyakit', 'type' => 'textarea', 'span' => 'full', 'placeholder' => 'cth: Hipertensi, Diabetes'],
    ]],
];

// Flatten definisi -> daftar field tunggal (fields = elemen ke-4 tiap seksi)
$fields = [];
foreach ($sections as $sec) $fields += $sec[3];

// Data awal (default per field)
$data = ['no_mr' => ''];
foreach ($fields as $key => $f) $data[$key] = $f['default'] ?? '';

if ($isEdit) {
    $stmt = db()->prepare("SELECT * FROM pasien WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { set_flash('danger', 'Pasien tidak ditemukan.'); legacy_redirect('modules/registrasi/pasien.php'); }
    $data = array_merge($data, $found);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sim_csrf_verify();
    foreach ($fields as $key => $f) $data[$key] = trim($_POST[$key] ?? '');

    if ($data['nama'] === '') $errors[] = 'Nama wajib diisi.';
    if (!in_array($data['jenis_kelamin'], ['L', 'P'], true)) $errors[] = 'Jenis kelamin tidak valid.';

    if (!$errors) {
        // Siapkan nilai untuk DB (kosong -> NULL, fk -> int)
        $store = [];
        foreach ($fields as $key => $f) {
            $v = $data[$key];
            if (($f['type'] ?? '') === 'fk') {
                $store[$key] = ($v === '' ? null : (int) $v);
            } else {
                $store[$key] = ($v === '' ? null : $v);
            }
        }

        if ($isEdit) {
            $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($store)));
            $params = array_values($store);
            $params[] = $id;
            db()->prepare("UPDATE pasien SET {$set} WHERE id = ?")->execute($params);
            set_flash('success', 'Data pasien berhasil diperbarui.');
            legacy_redirect('modules/registrasi/pasien.php?q=' . urlencode($data['nama']));
        } else {
            // generate No. MR: GBK0001
            $next = (int) db()->query("SELECT COALESCE(MAX(id),0)+1 FROM pasien")->fetchColumn();
            $noMr = 'GBK' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $cols = array_merge(['no_mr'], array_keys($store));
            $ph   = implode(', ', array_fill(0, count($cols), '?'));
            $vals = array_merge([$noMr], array_values($store));
            db()->prepare("INSERT INTO pasien (" . implode(', ', $cols) . ") VALUES ({$ph})")->execute($vals);
            $newId = (int) db()->lastInsertId();
            set_flash('success', "Pasien baru ($noMr) berhasil disimpan. Lanjutkan daftar kunjungan.");
            legacy_redirect('modules/registrasi/daftar.php?pasien_id=' . $newId);
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= e($pageTitle) ?></div>
    <div class="pt-sub"><?= $isEdit ? 'No. MR: <b>' . e($data['no_mr']) . '</b>' : 'No. MR dibuat otomatis saat disimpan' ?></div>
  </div>
  <div class="pt-actions">
    <a class="btn-back" href="<?= legacy_url('modules/registrasi/pasien.php') ?>"><?= app_icon('chevron') ?> <?= e(t('common.back')) ?></a>
  </div>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?= implode('<br>', array_map('e', $errors)) ?></div>
<?php endif; ?>

<form method="post">
  <?= sim_csrf_field() ?>
  <?php foreach ($sections as [$secTitle, $secIcon, $secAcc, $secFields]): ?>
    <div class="card" style="margin-bottom:16px">
      <div class="step-head">
        <div class="step-num <?= e($secAcc) ?>"><?= app_icon($secIcon) ?></div>
        <div><div class="st-title"><?= e($secTitle) ?></div></div>
      </div>
      <div class="field-grid">
        <?php foreach ($secFields as $key => $f): $val = $data[$key] ?? ''; $req = !empty($f['required']); ?>
          <div class="form-group<?= ($f['span'] ?? '') === 'full' ? ' fg-full' : '' ?>">
            <label><?= e($f['label']) ?><?= $req ? ' <span class="req">*</span>' : '' ?></label>
            <?php
              $attr = ($req ? ' required' : '')
                    . (!empty($f['autofocus']) ? ' autofocus' : '')
                    . (!empty($f['inputmode']) ? ' inputmode="' . e($f['inputmode']) . '"' : '')
                    . (!empty($f['placeholder']) ? ' placeholder="' . e($f['placeholder']) . '"' : '');
              $type = $f['type'];
            ?>
            <?php if ($type === 'textarea'): ?>
              <textarea name="<?= $key ?>" class="form-control" rows="2"<?= $attr ?>><?= e($val) ?></textarea>

            <?php elseif ($type === 'select'): ?>
              <select name="<?= $key ?>" class="form-control"<?= $req ? ' required' : '' ?>>
                <?php if (!$req): ?><option value="">- Pilih -</option><?php endif; ?>
                <?php foreach ($f['options'] as $ov => $ol): ?>
                  <option value="<?= e($ov) ?>" <?= (string) $val === (string) $ov ? 'selected' : '' ?>><?= e($ol) ?></option>
                <?php endforeach; ?>
              </select>

            <?php elseif ($type === 'fk'): ?>
              <select name="<?= $key ?>" class="form-control">
                <option value="">- Pilih -</option>
                <?php foreach ($kelompok as $k): ?>
                  <option value="<?= $k['id'] ?>" <?= (string) $val === (string) $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option>
                <?php endforeach; ?>
              </select>

            <?php else: /* text | date | email | tel | number */ ?>
              <input type="<?= e($type) ?>" name="<?= $key ?>" class="form-control" value="<?= e($val) ?>"<?= $attr ?>>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="form-actions" style="position:sticky;bottom:0;background:var(--bg);padding:14px 0">
    <a class="btn btn-light" href="<?= legacy_url('modules/registrasi/pasien.php') ?>"><?= e(t('common.cancel')) ?></a>
    <button class="btn" type="submit"><?= app_icon('save') ?> <?= e(t('common.save_patient')) ?></button>
  </div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
