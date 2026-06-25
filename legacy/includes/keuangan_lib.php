<?php
/**
 * Library keuangan: pembuatan invoice dari billing & rekalkulasi status bayar.
 */
require_once __DIR__ . '/functions.php';

/**
 * Ambil invoice untuk sebuah kunjungan; buat otomatis dari billing final jika belum ada.
 * @return array|null Baris invoice, atau null jika billing belum final.
 */
function get_or_create_invoice(int $kunjunganId): ?array
{
    $inv = db()->prepare("SELECT * FROM invoice WHERE kunjungan_id=?");
    $inv->execute([$kunjunganId]);
    if ($row = $inv->fetch()) return $row;

    // butuh billing final
    $b = db()->prepare("SELECT * FROM billing WHERE kunjungan_id=? AND status='final'");
    $b->execute([$kunjunganId]);
    $billing = $b->fetch();
    if (!$billing) return null;

    // No. Invoice gaya struk client: GBRI + 2 digit tahun + 8 digit urut (mis. GBRI2600000001)
    $prefix = 'GBRI' . date('y');
    $s = db()->prepare("SELECT MAX(no_invoice) FROM invoice WHERE no_invoice LIKE ?");
    $s->execute([$prefix . '%']);
    $maxInv = $s->fetchColumn();
    $nextInv = $maxInv ? (int) substr((string) $maxInv, strlen($prefix)) + 1 : 1;
    $noInvoice = $prefix . str_pad((string) $nextInv, 8, '0', STR_PAD_LEFT);
    db()->prepare(
        "INSERT INTO invoice (no_invoice,billing_id,kunjungan_id,tanggal,total,terbayar,status)
         VALUES (?,?,?,?,?,0,'belum_bayar')")
      ->execute([$noInvoice, $billing['id'], $kunjunganId, date('Y-m-d'), $billing['total']]);

    $inv = db()->prepare("SELECT * FROM invoice WHERE id=?");
    $inv->execute([(int) db()->lastInsertId()]);
    return $inv->fetch();
}

/**
 * Hitung ulang terbayar & status invoice dari pembayaran valid.
 * Jika lunas, kunjungan diset 'selesai'. Mengembalikan status invoice baru.
 */
function recompute_invoice(int $invoiceId): string
{
    $inv = db()->prepare("SELECT * FROM invoice WHERE id=?");
    $inv->execute([$invoiceId]);
    $invoice = $inv->fetch();
    if (!$invoice) return 'belum_bayar';

    $terbayar = (float) db()->query(
        "SELECT COALESCE(SUM(jumlah),0) FROM pembayaran WHERE invoice_id={$invoiceId} AND status='valid'")
        ->fetchColumn();

    $total = (float) $invoice['total'];
    if ($terbayar <= 0)            $status = 'belum_bayar';
    elseif ($terbayar < $total)    $status = 'sebagian';
    else                            $status = 'lunas';

    db()->prepare("UPDATE invoice SET terbayar=?, status=? WHERE id=?")
      ->execute([$terbayar, $status, $invoiceId]);

    if ($status === 'lunas') {
        db()->prepare("UPDATE kunjungan SET status='selesai' WHERE id=?")->execute([$invoice['kunjungan_id']]);
    }
    return $status;
}

/** Label metode pembayaran */
function metode_label(string $m): string
{
    return [
        'cash' => 'Tunai', 'transfer' => 'Transfer Bank', 'qris' => 'QRIS',
        'edc' => 'EDC/Debit', 'va' => 'Virtual Account', 'ewallet' => 'E-Wallet',
        'penjamin' => 'Penjamin (Asuransi/BPJS/Corporate)',
    ][$m] ?? ucfirst($m);
}
