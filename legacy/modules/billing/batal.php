<?php
/**
 * Pembatalan billing / kunjungan — wajib pilih kode pembatalan dari master data.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_role('kasir', 'admin', 'superadmin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    legacy_redirect('modules/billing/index.php');
}

sim_csrf_verify();

$kunjunganId = (int) ($_POST['kunjungan_id'] ?? 0);
$kodePembatalanId = (int) ($_POST['kode_pembatalan_id'] ?? 0);
$alasan = trim($_POST['alasan_batal'] ?? '');
$redirect = $_POST['redirect'] ?? 'modules/billing/index.php';

$kj = db()->prepare("SELECT k.*, i.id AS invoice_id, i.terbayar FROM kunjungan k LEFT JOIN invoice i ON i.kunjungan_id = k.id WHERE k.id = ?");
$kj->execute([$kunjunganId]);
$kj = $kj->fetch();

if (!$kj) {
    set_flash('danger', 'Kunjungan tidak ditemukan.');
    legacy_redirect($redirect);
}

if ($kj['status'] === 'batal') {
    set_flash('warning', 'Kunjungan ini sudah dibatalkan sebelumnya.');
    legacy_redirect($redirect);
}

if ($kj['status'] === 'selesai' || ((float) ($kj['terbayar'] ?? 0)) > 0) {
    set_flash('danger', 'Tidak dapat membatalkan kunjungan yang sudah dibayar atau selesai.');
    legacy_redirect($redirect);
}

$kp = db()->prepare("SELECT id, kode, nama FROM kode_pembatalan WHERE id = ? AND status = 'aktif'");
$kp->execute([$kodePembatalanId]);
$kp = $kp->fetch();

if (!$kp) {
    set_flash('danger', 'Kode pembatalan wajib dipilih.');
    legacy_redirect($redirect);
}

try {
    db()->beginTransaction();

    db()->prepare(
        "UPDATE kunjungan SET status='batal', kode_pembatalan_id=?, alasan_batal=?, batal_at=NOW(), batal_by=? WHERE id=?"
    )->execute([
        $kodePembatalanId,
        $alasan !== '' ? $alasan : $kp['nama'],
        current_user()['id'],
        $kunjunganId,
    ]);

    $billing = db()->prepare("SELECT id FROM billing WHERE kunjungan_id = ?");
    $billing->execute([$kunjunganId]);
    $billingId = $billing->fetchColumn();

    if ($billingId) {
        db()->prepare("DELETE FROM billing_detail WHERE billing_id = ?")->execute([$billingId]);
        db()->prepare("DELETE FROM billing WHERE id = ?")->execute([$billingId]);
    }

    if (!empty($kj['invoice_id'])) {
        db()->prepare("DELETE FROM pembayaran WHERE invoice_id = ?")->execute([$kj['invoice_id']]);
        db()->prepare("DELETE FROM invoice WHERE id = ?")->execute([$kj['invoice_id']]);
    }

    db()->commit();
    set_flash('success', 'Billing dibatalkan dengan kode ' . $kp['kode'] . ' — ' . $kp['nama'] . '.');
} catch (Throwable $ex) {
    if (db()->inTransaction()) db()->rollBack();
    set_flash('danger', 'Gagal membatalkan billing: ' . $ex->getMessage());
}

legacy_redirect($redirect);
