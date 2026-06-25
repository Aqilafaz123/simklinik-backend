<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/icons.php'; // app_icon() juga dipakai saat mode modal (tanpa header.php)
require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna';

// Mode modal: hanya kirim potongan form (tanpa header/sidebar), submit via fetch
$modal = isset($_GET['modal']);
$formAction = legacy_url('modules/pengaturan/user_form.php' . ($isEdit ? '?id=' . $id . ($modal ? '&modal=1' : '') : ($modal ? '?modal=1' : '')));

$roles = db()->query("SELECT id, kode, nama FROM roles ORDER BY id")->fetchAll();
$poliList = db()->query("SELECT id, nama FROM poli WHERE status='aktif' ORDER BY nama")->fetchAll();
// Peta role_id => kode, untuk menentukan apakah role yang dipilih adalah dokter.
$roleKode = [];
foreach ($roles as $r) $roleKode[(string) $r['id']] = $r['kode'];

$data = ['nama' => '', 'username' => '', 'email' => '', 'telepon' => '', 'role_id' => '', 'poli_id' => '', 'status' => 'aktif'];
if ($isEdit) {
    $s = db()->prepare("SELECT * FROM users WHERE id=?"); $s->execute([$id]);
    $row = $s->fetch();
    if (!$row) { set_flash('danger', 'Pengguna tidak ditemukan.'); legacy_redirect('modules/pengaturan/users.php'); }
    $data = array_merge($data, $row);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sim_csrf_verify();
    foreach (['nama', 'username', 'email', 'telepon', 'role_id', 'poli_id', 'status'] as $k) $data[$k] = trim($_POST[$k] ?? '');
    $password = $_POST['password'] ?? '';

    // Poli hanya relevan untuk role Dokter; abaikan untuk role lain.
    $isDokter = ($roleKode[$data['role_id']] ?? '') === 'dokter';
    $poliId = ($isDokter && $data['poli_id'] !== '') ? (int) $data['poli_id'] : null;

    if ($data['nama'] === '') $errors[] = 'Nama wajib diisi.';
    if ($data['username'] === '') $errors[] = 'Username wajib diisi.';
    if (!$data['role_id']) $errors[] = 'Role wajib dipilih.';
    if ($isDokter && !$poliId) $errors[] = 'Poli wajib dipilih untuk role Dokter.';
    if (!$isEdit && $password === '') $errors[] = 'Password wajib diisi untuk pengguna baru.';
    if ($password !== '' && strlen($password) < 5) $errors[] = 'Password minimal 5 karakter.';

    if (!$errors) {
        try {
            if ($isEdit) {
                if ($password !== '') {
                    db()->prepare("UPDATE users SET nama=?, username=?, email=?, telepon=?, role_id=?, poli_id=?, status=?, password=? WHERE id=?")
                      ->execute([$data['nama'], $data['username'], $data['email'] ?: null, $data['telepon'] ?: null,
                          (int)$data['role_id'], $poliId, $data['status'], password_hash($password, PASSWORD_BCRYPT), $id]);
                } else {
                    db()->prepare("UPDATE users SET nama=?, username=?, email=?, telepon=?, role_id=?, poli_id=?, status=? WHERE id=?")
                      ->execute([$data['nama'], $data['username'], $data['email'] ?: null, $data['telepon'] ?: null,
                          (int)$data['role_id'], $poliId, $data['status'], $id]);
                }
                set_flash('success', 'Pengguna berhasil diperbarui.');
            } else {
                db()->prepare("INSERT INTO users (nama, username, email, telepon, role_id, poli_id, status, password) VALUES (?,?,?,?,?,?,?,?)")
                  ->execute([$data['nama'], $data['username'], $data['email'] ?: null, $data['telepon'] ?: null,
                      (int)$data['role_id'], $poliId, $data['status'], password_hash($password, PASSWORD_BCRYPT)]);
                set_flash('success', 'Pengguna baru berhasil ditambahkan.');
            }
            if ($modal) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => true]);
                exit;
            }
            legacy_redirect('modules/pengaturan/users.php');
        } catch (Throwable $ex) {
            $errors[] = (str_contains($ex->getMessage(), '1062')) ? 'Username sudah dipakai.' : 'Gagal menyimpan: ' . $ex->getMessage();
        }
    }
}

// Tampilkan field Poli sejak awal bila role terpilih adalah dokter.
$showPoli = ($roleKode[(string) $data['role_id']] ?? '') === 'dokter';

if (!$modal):
    require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= app_icon("user") ?> <?= e($pageTitle) ?></div>
    <div class="pt-sub"><?= $isEdit ? 'Perbarui data akun & hak akses pengguna.' : 'Buat akun baru beserta role hak aksesnya.' ?></div>
  </div>
  <div class="pt-actions">
    <a href="<?= legacy_url('modules/pengaturan/users.php') ?>" class="btn btn-light btn-sm"><?= app_icon("arrowleft") ?> Pengguna</a>
  </div>
</div>
<div class="card" style="max-width:720px;margin-top:18px">
<?php endif; ?>

  <?php if ($errors): ?><div class="alert alert-danger"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

  <form method="post" action="<?= e($formAction) ?>"<?= $modal ? ' data-modal-form' : '' ?>>
    <?= sim_csrf_field() ?>
    <div class="form-row">
      <div class="form-group"><label>Nama Lengkap <span class="req">*</span></label><input type="text" name="nama" class="form-control" value="<?= e($data['nama']) ?>" required></div>
      <div class="form-group"><label>Username <span class="req">*</span></label><input type="text" name="username" class="form-control" value="<?= e($data['username']) ?>" required></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Role <span class="req">*</span></label>
        <select name="role_id" class="form-control" id="roleSelect" required
          onchange="var o=this.options[this.selectedIndex];document.getElementById('poliRow').style.display=(o&&o.getAttribute('data-kode')==='dokter')?'':'none';">
          <option value="">- Pilih -</option>
          <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>" data-kode="<?= e($r['kode']) ?>" <?= (string)$data['role_id'] === (string)$r['id'] ? 'selected' : '' ?>><?= e($r['nama']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Status</label>
        <select name="status" class="form-control">
          <option value="aktif" <?= $data['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
          <option value="nonaktif" <?= $data['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
      </div>
    </div>
    <div class="form-row" id="poliRow" style="display:<?= $showPoli ? '' : 'none' ?>">
      <div class="form-group"><label>Poli <span class="req">*</span></label>
        <select name="poli_id" class="form-control" id="poliSelect">
          <option value="">- Pilih Poli -</option>
          <?php foreach ($poliList as $po): ?><option value="<?= $po['id'] ?>" <?= (string)$data['poli_id'] === (string)$po['id'] ? 'selected' : '' ?>><?= e($po['nama']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>&nbsp;</label>
        <div class="poli-hint"><?= app_icon('alert') ?> <span>Antrian pemeriksaan dokter ini akan disaring sesuai poli yang dipilih.</span></div>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= e($data['email']) ?>"></div>
      <div class="form-group"><label>Telepon</label><input type="text" name="telepon" class="form-control" value="<?= e($data['telepon']) ?>"></div>
    </div>
    <div class="form-group">
      <label>Password <?= $isEdit ? '(kosongkan jika tidak diganti)' : '<span class="req">*</span>' ?></label>
      <input type="password" name="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit"><?= app_icon("save") ?> Simpan</button>
      <?php if ($modal): ?>
        <button type="button" class="btn btn-light" data-modal-close>Batal</button>
      <?php else: ?>
        <a class="btn btn-light" href="<?= legacy_url('modules/pengaturan/users.php') ?>">Batal</a>
      <?php endif; ?>
    </div>
  </form>

<?php if (!$modal): ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?php endif; ?>
