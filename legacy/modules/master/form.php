<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/icons.php'; // app_icon() juga dipakai saat mode modal (tanpa header.php)
require_once __DIR__ . '/entities.php';
require_role('superadmin');

$slug = $_GET['e'] ?? '';
$ent = master_entity($slug);
if (!$ent) { set_flash('danger', 'Entitas tidak dikenal.'); legacy_redirect('modules/master/index.php'); }

$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$pageTitle = ($isEdit ? 'Edit ' : 'Tambah ') . $ent['singular'];

// Mode modal: hanya kirim potongan form (tanpa header/sidebar), submit via fetch
$modal = isset($_GET['modal']);
$formAction = legacy_url('modules/master/form.php?e=' . $slug . ($isEdit ? '&id=' . $id : '') . ($modal ? '&modal=1' : ''));

// field yang bisa diisi user (selain readonly)
$editable = array_filter($ent['fields'], fn($f) => $f['type'] !== 'readonly');

// data awal
$data = [];
foreach ($ent['fields'] as $key => $f) {
    $data[$key] = $f['default'] ?? '';
}
if ($isEdit) {
    $stmt = db()->prepare("SELECT * FROM {$ent['table']} WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) { set_flash('danger', 'Data tidak ditemukan.'); legacy_redirect('modules/master/index.php?e=' . $slug); }
    $data = array_merge($data, $row);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sim_csrf_verify();
    foreach ($editable as $key => $f) {
        $data[$key] = trim($_POST[$key] ?? '');
    }
    // validasi required
    foreach ($editable as $key => $f) {
        if (!empty($f['required']) && $data[$key] === '') {
            $errors[] = $f['label'] . ' wajib diisi.';
        }
    }

    if (!$errors) {
        // siapkan kolom & nilai
        $cols = []; $vals = [];
        foreach ($editable as $key => $f) {
            $v = $data[$key];
            if ($f['type'] === 'fk') {
                $v = ($v === '' ? null : (int) $v);
            } elseif (in_array($f['type'], ['number', 'money'], true)) {
                $v = ($v === '' ? 0 : (float) $v);
            } elseif ($v === '') {
                $v = null;
            }
            $cols[$key] = $v;
        }
        // generate kode GBK saat create
        if (!$isEdit && isset($ent['code'])) {
            $cols['kode'] = generate_item_code($ent['code']['jenis'], $ent['table']);
        }

        try {
            if ($isEdit) {
                $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($cols)));
                $params = array_values($cols); $params[] = $id;
                db()->prepare("UPDATE {$ent['table']} SET {$set} WHERE id = ?")->execute($params);
                set_flash('success', $ent['singular'] . ' berhasil diperbarui.');
            } else {
                $colNames = implode(', ', array_keys($cols));
                $ph = implode(', ', array_fill(0, count($cols), '?'));
                db()->prepare("INSERT INTO {$ent['table']} ({$colNames}) VALUES ({$ph})")->execute(array_values($cols));
                set_flash('success', $ent['singular'] . ' berhasil ditambahkan.');
            }
            if ($modal) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => true]);
                exit;
            }
            legacy_redirect('modules/master/index.php?e=' . $slug);
        } catch (Throwable $ex) {
            $msg = $ex->getMessage();
            if (str_contains($msg, '1062') || stripos($msg, 'duplicate') !== false) {
                $errors[] = 'Data dengan kode/nama tersebut sudah ada (duplikat).';
            } else {
                $errors[] = 'Gagal menyimpan: ' . $msg;
            }
        }
    }
}

if (!$modal):
    require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= e($pageTitle) ?></div>
    <div class="pt-sub">Master Data &middot; <?= e($ent['label']) ?></div>
  </div>
  <div class="pt-actions">
    <a class="btn-back" href="<?= legacy_url('modules/master/index.php?e=' . $slug) ?>"><?= app_icon('chevron') ?> Kembali ke <?= e($ent['label']) ?></a>
  </div>
</div>
<div class="card" style="max-width:760px;margin-top:16px">
<?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert-danger"><?= implode('<br>', array_map('e', $errors)) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e($formAction) ?>"<?= $modal ? ' data-modal-form' : '' ?>>
    <?= sim_csrf_field() ?>
    <div class="form-grid">
    <?php foreach ($ent['fields'] as $key => $f): ?>
      <?php
        $val = $data[$key] ?? '';
        $full = in_array($f['type'], ['textarea', 'readonly'], true); // span 2 kolom
      ?>
      <div class="form-group<?= $full ? ' fg-full' : '' ?>">
        <label><?= e($f['label']) ?><?= !empty($f['required']) ? '<span class="req">*</span>' : '' ?></label>
        <?php if ($f['type'] === 'readonly'): ?>
          <input class="form-control" value="<?= $isEdit ? e($val) : '(otomatis saat simpan)' ?>" disabled
                 style="background:var(--bg);color:var(--muted)">

        <?php elseif ($f['type'] === 'textarea'): ?>
          <textarea class="form-control" name="<?= $key ?>" rows="2"><?= e($val) ?></textarea>

        <?php elseif ($f['type'] === 'enum'): ?>
          <select class="form-control" name="<?= $key ?>">
            <?php foreach ($f['options'] as $opt): ?>
              <option value="<?= e($opt) ?>" <?= (string) $val === (string) $opt ? 'selected' : '' ?>><?= e(ucfirst($opt)) ?></option>
            <?php endforeach; ?>
          </select>

        <?php elseif ($f['type'] === 'fk'): ?>
          <?php $map = fk_map($f['fk_table'], $f['fk_label']); ?>
          <select class="form-control" name="<?= $key ?>" <?= !empty($f['required']) ? 'required' : '' ?>>
            <option value="">- Pilih -</option>
            <?php foreach ($map as $optId => $optLbl): ?>
              <option value="<?= $optId ?>" <?= (string) $val === (string) $optId ? 'selected' : '' ?>><?= e($optLbl) ?></option>
            <?php endforeach; ?>
          </select>

        <?php elseif ($f['type'] === 'time'): ?>
          <input type="time" class="form-control" name="<?= $key ?>" value="<?= e($val) ?>">

        <?php elseif ($f['type'] === 'date'): ?>
          <input type="date" class="form-control" name="<?= $key ?>" value="<?= e($val) ?>">

        <?php elseif ($f['type'] === 'money'): ?>
          <div class="input-money">
            <span class="cur">Rp</span>
            <input type="number" class="form-control" name="<?= $key ?>" value="<?= e($val) ?>"
                   step="any" min="0" <?= !empty($f['required']) ? 'required' : '' ?>>
          </div>

        <?php elseif ($f['type'] === 'number'): ?>
          <input type="number" class="form-control" name="<?= $key ?>" value="<?= e($val) ?>"
                 step="1" min="0" <?= !empty($f['required']) ? 'required' : '' ?>>

        <?php else: /* text */ ?>
          <input type="text" class="form-control" name="<?= $key ?>" value="<?= e($val) ?>"
                 <?= !empty($f['required']) ? 'required' : '' ?>>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit"><?= app_icon('save') ?> Simpan</button>
      <?php if ($modal): ?>
        <button type="button" class="btn btn-light" data-modal-close>Batal</button>
      <?php else: ?>
        <a class="btn btn-light" href="<?= legacy_url('modules/master/index.php?e=' . $slug) ?>">Batal</a>
      <?php endif; ?>
    </div>
  </form>

<?php if (!$modal): ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?php endif; ?>
