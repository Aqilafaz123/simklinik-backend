<?php
/**
 * Registry entitas Master Data untuk mesin CRUD generik.
 * Tiap entitas mendefinisikan tabel, grup, ikon, dan daftar field.
 *
 * Tipe field: text | number | money | textarea | enum | fk | time | date | readonly
 *   - 'code' => ['jenis'=>...]  : kode di-generate otomatis (GBK...) saat create
 *   - 'list' => true            : tampil di tabel daftar
 *   - 'required' => true        : wajib diisi
 *   - 'fk_table','fk_label'     : sumber pilihan untuk tipe fk
 *   - 'options'                 : pilihan untuk tipe enum
 */
require_once __DIR__ . '/../../includes/master_lib.php';
require_once __DIR__ . '/../../includes/icons.php';

function master_entities(): array
{
    return [
        // ---------- Layanan & Tarif ----------
        'tindakan' => [
            'label' => 'Medical Service', 'singular' => 'Medical Service',
            'table' => 'tindakan', 'group' => 'Layanan & Tarif', 'icon' => app_icon('syringe'), 'order' => 'nama',
            'code' => ['jenis' => 'tindakan'],
            'fields' => [
                'kode'      => ['label' => 'Item Code', 'type' => 'readonly', 'list' => true],
                'nama'      => ['label' => 'Nama Medical Service', 'type' => 'text', 'required' => true, 'list' => true],
                // 'kode_icd9' => ['label' => 'Kode ICD-9-CM', 'type' => 'text'],
                'tarif'     => ['label' => 'Tarif', 'type' => 'money', 'list' => true],
                'status'    => ['label' => 'Status', 'type' => 'enum', 'options' => ['aktif', 'nonaktif'], 'default' => 'aktif', 'list' => true],
            ],
        ],
        'lab_kategori' => [
            'label' => 'Kategori Laboratorium', 'tab' => 'Kat. Lab', 'singular' => 'Kategori Lab',
            'table' => 'lab_kategori', 'group' => 'Layanan & Tarif', 'icon' => app_icon('flask'), 'order' => 'nama',
            'fields' => [
                'nama' => ['label' => 'Nama Kategori', 'type' => 'text', 'required' => true, 'list' => true],
            ],
        ],
        'lab_pemeriksaan' => [
            'label' => 'Pemeriksaan Laboratorium', 'tab' => 'Lab', 'singular' => 'Pemeriksaan Lab',
            'table' => 'lab_pemeriksaan', 'group' => 'Layanan & Tarif', 'icon' => app_icon('flask'), 'order' => 'nama',
            'code' => ['jenis' => 'lab'],
            'fields' => [
                'kode'          => ['label' => 'Item Code', 'type' => 'readonly', 'list' => true],
                'kategori_id'   => ['label' => 'Kategori', 'type' => 'fk', 'fk_table' => 'lab_kategori', 'fk_label' => 'nama', 'list' => true],
                'nama'          => ['label' => 'Nama Pemeriksaan', 'type' => 'text', 'required' => true, 'list' => true],
                'satuan'        => ['label' => 'Satuan', 'type' => 'text'],
                'nilai_rujukan' => ['label' => 'Nilai Rujukan', 'type' => 'text'],
                'tarif'         => ['label' => 'Tarif', 'type' => 'money', 'list' => true],
                'markup_persen' => ['label' => 'Markup (%)', 'type' => 'percent'],
                'status'        => ['label' => 'Status', 'type' => 'enum', 'options' => ['aktif', 'nonaktif'], 'default' => 'aktif', 'list' => true],
            ],
        ],
        'rad_kategori' => [
            'label' => 'Kategori Radiologi', 'tab' => 'Kat. Radiologi', 'singular' => 'Kategori Radiologi',
            'table' => 'rad_kategori', 'group' => 'Layanan & Tarif', 'icon' => app_icon('scan'), 'order' => 'nama',
            'fields' => [
                'nama' => ['label' => 'Nama Kategori', 'type' => 'text', 'required' => true, 'list' => true],
            ],
        ],
        'rad_pemeriksaan' => [
            'label' => 'Pemeriksaan Radiologi', 'tab' => 'Radiologi', 'singular' => 'Pemeriksaan Radiologi',
            'table' => 'rad_pemeriksaan', 'group' => 'Layanan & Tarif', 'icon' => app_icon('scan'), 'order' => 'nama',
            'code' => ['jenis' => 'radiologi'],
            'fields' => [
                'kode'        => ['label' => 'Item Code', 'type' => 'readonly', 'list' => true],
                'kategori_id' => ['label' => 'Kategori', 'type' => 'fk', 'fk_table' => 'rad_kategori', 'fk_label' => 'nama', 'list' => true],
                'nama'        => ['label' => 'Nama Pemeriksaan', 'type' => 'text', 'required' => true, 'list' => true],
                'tarif'       => ['label' => 'Tarif', 'type' => 'money', 'list' => true],
                'markup_persen' => ['label' => 'Markup (%)', 'type' => 'percent'],
                'status'      => ['label' => 'Status', 'type' => 'enum', 'options' => ['aktif', 'nonaktif'], 'default' => 'aktif', 'list' => true],
            ],
        ],
        'diag_kategori' => [
            'label' => 'Kategori Diagnostik', 'tab' => 'Kat. Diagnostik', 'singular' => 'Kategori Diagnostik',
            'table' => 'diag_kategori', 'group' => 'Layanan & Tarif', 'icon' => app_icon('monitor'), 'order' => 'nama',
            'fields' => [
                'nama' => ['label' => 'Nama Kategori', 'type' => 'text', 'required' => true, 'list' => true],
            ],
        ],
        'diag_pemeriksaan' => [
            'label' => 'Pemeriksaan Diagnostik', 'tab' => 'Diagnostik', 'singular' => 'Pemeriksaan Diagnostik',
            'table' => 'diag_pemeriksaan', 'group' => 'Layanan & Tarif', 'icon' => app_icon('monitor'), 'order' => 'nama',
            'code' => ['jenis' => 'diagnostik'],
            'fields' => [
                'kode'        => ['label' => 'Item Code', 'type' => 'readonly', 'list' => true],
                'kategori_id' => ['label' => 'Kategori', 'type' => 'fk', 'fk_table' => 'diag_kategori', 'fk_label' => 'nama', 'list' => true],
                'nama'        => ['label' => 'Nama Pemeriksaan', 'type' => 'text', 'required' => true, 'list' => true],
                'tarif'       => ['label' => 'Tarif', 'type' => 'money', 'list' => true],
                'status'      => ['label' => 'Status', 'type' => 'enum', 'options' => ['aktif', 'nonaktif'], 'default' => 'aktif', 'list' => true],
            ],
        ],
        'fisio_kategori' => [
            'label' => 'Kategori Fisioterapi', 'tab' => 'Kat. Fisioterapi', 'singular' => 'Kategori Fisioterapi',
            'table' => 'fisio_kategori', 'group' => 'Layanan & Tarif', 'icon' => app_icon('pelayanan'), 'order' => 'nama',
            'fields' => [
                'nama' => ['label' => 'Nama Kategori', 'type' => 'text', 'required' => true, 'list' => true],
            ],
        ],
        'fisio_pemeriksaan' => [
            'label' => 'Layanan Fisioterapi', 'tab' => 'Fisioterapi', 'singular' => 'Layanan Fisioterapi',
            'table' => 'fisio_pemeriksaan', 'group' => 'Layanan & Tarif', 'icon' => app_icon('pelayanan'), 'order' => 'nama',
            'code' => ['jenis' => 'fisioterapi'],
            'fields' => [
                'kode'        => ['label' => 'Item Code', 'type' => 'readonly', 'list' => true],
                'kategori_id' => ['label' => 'Kategori', 'type' => 'fk', 'fk_table' => 'fisio_kategori', 'fk_label' => 'nama', 'list' => true],
                'nama'        => ['label' => 'Nama Layanan', 'type' => 'text', 'required' => true, 'list' => true],
                'tarif'       => ['label' => 'Tarif', 'type' => 'money', 'list' => true],
                'status'      => ['label' => 'Status', 'type' => 'enum', 'options' => ['aktif', 'nonaktif'], 'default' => 'aktif', 'list' => true],
            ],
        ],

        // ---------- SDM & Poli ----------
        'spesialisasi' => [
            'label' => 'Spesialisasi Dokter', 'singular' => 'Spesialisasi',
            'table' => 'spesialisasi', 'group' => 'SDM & Poli', 'icon' => app_icon('award'), 'order' => 'nama',
            'fields' => [
                'nama' => ['label' => 'Nama Spesialisasi', 'type' => 'text', 'required' => true, 'list' => true],
            ],
        ],
        'dokter' => [
            'label' => 'Dokter', 'singular' => 'Dokter',
            'table' => 'dokter', 'group' => 'SDM & Poli', 'icon' => app_icon('user'), 'order' => 'nama',
            'code' => ['jenis' => 'dokter'],
            'fields' => [
                'kode'            => ['label' => 'Kode', 'type' => 'readonly', 'list' => true],
                'nama'            => ['label' => 'Nama Dokter', 'type' => 'text', 'required' => true, 'list' => true],
                'spesialisasi_id' => ['label' => 'Spesialisasi', 'type' => 'fk', 'fk_table' => 'spesialisasi', 'fk_label' => 'nama', 'list' => true],
                'poli_id'         => ['label' => 'Poli', 'type' => 'fk', 'fk_table' => 'poli', 'fk_label' => 'nama', 'list' => true],
                'no_sip'          => ['label' => 'No. SIP', 'type' => 'text'],
                'telepon'         => ['label' => 'Telepon', 'type' => 'text'],
                'tarif_jasa'      => ['label' => 'Tarif Jasa', 'type' => 'money', 'list' => true],
                'status'          => ['label' => 'Status', 'type' => 'enum', 'options' => ['aktif', 'nonaktif'], 'default' => 'aktif', 'list' => true],
            ],
        ],
        'poli' => [
            'label' => 'Poli', 'singular' => 'Poli',
            'table' => 'poli', 'group' => 'SDM & Poli', 'icon' => app_icon('hospital'), 'order' => 'nama',
            'fields' => [
                'kode'   => ['label' => 'Kode Poli (prefix antrian)', 'type' => 'text', 'required' => true, 'list' => true],
                'nama'   => ['label' => 'Nama Poli', 'type' => 'text', 'required' => true, 'list' => true],
                'status' => ['label' => 'Status', 'type' => 'enum', 'options' => ['aktif', 'nonaktif'], 'default' => 'aktif', 'list' => true],
            ],
        ],
        'jadwal_dokter' => [
            'label' => 'Jadwal Praktik Dokter', 'singular' => 'Jadwal',
            'table' => 'jadwal_dokter', 'group' => 'SDM & Poli', 'icon' => app_icon('calendar'), 'order' => 'id',
            'fields' => [
                'dokter_id'   => ['label' => 'Dokter', 'type' => 'fk', 'fk_table' => 'dokter', 'fk_label' => 'nama', 'required' => true, 'list' => true],
                'poli_id'     => ['label' => 'Poli', 'type' => 'fk', 'fk_table' => 'poli', 'fk_label' => 'nama', 'required' => true, 'list' => true],
                'hari'        => ['label' => 'Hari', 'type' => 'enum', 'options' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'], 'list' => true],
                'jam_mulai'   => ['label' => 'Jam Mulai', 'type' => 'time', 'list' => true],
                'jam_selesai' => ['label' => 'Jam Selesai', 'type' => 'time', 'list' => true],
                'kuota'       => ['label' => 'Kuota (0=tak terbatas)', 'type' => 'number', 'list' => true],
            ],
        ],

        // ---------- Farmasi ----------
        'obat_kategori' => [
            'label' => 'Kategori Obat', 'singular' => 'Kategori Obat',
            'table' => 'obat_kategori', 'group' => 'Farmasi', 'icon' => app_icon('tag'), 'order' => 'nama',
            'fields' => [
                'nama' => ['label' => 'Nama Kategori', 'type' => 'text', 'required' => true, 'list' => true],
            ],
        ],
        'obat_satuan' => [
            'label' => 'Satuan Obat', 'singular' => 'Satuan',
            'table' => 'obat_satuan', 'group' => 'Farmasi', 'icon' => app_icon('ruler'), 'order' => 'nama',
            'fields' => [
                'nama' => ['label' => 'Nama Satuan', 'type' => 'text', 'required' => true, 'list' => true],
            ],
        ],
        'supplier' => [
            'label' => 'Supplier Obat', 'singular' => 'Supplier',
            'table' => 'supplier', 'group' => 'Farmasi', 'icon' => app_icon('truck'), 'order' => 'nama',
            'fields' => [
                'nama'    => ['label' => 'Nama Supplier', 'type' => 'text', 'required' => true, 'list' => true],
                'kontak'  => ['label' => 'Kontak', 'type' => 'text', 'list' => true],
                'telepon' => ['label' => 'Telepon', 'type' => 'text', 'list' => true],
                'alamat'  => ['label' => 'Alamat', 'type' => 'textarea'],
            ],
        ],
        'obat' => [
            'label' => 'Data Obat', 'singular' => 'Obat',
            'table' => 'obat', 'group' => 'Farmasi', 'icon' => app_icon('pills'), 'order' => 'nama',
            'code' => ['jenis' => 'obat'],
            'fields' => [
                'kode'         => ['label' => 'Item Code', 'type' => 'readonly', 'list' => true],
                'nama'         => ['label' => 'Nama Obat', 'type' => 'text', 'required' => true, 'list' => true],
                'kategori_id'  => ['label' => 'Kategori', 'type' => 'fk', 'fk_table' => 'obat_kategori', 'fk_label' => 'nama', 'list' => true],
                'satuan_id'    => ['label' => 'Satuan', 'type' => 'fk', 'fk_table' => 'obat_satuan', 'fk_label' => 'nama'],
                'harga_beli'   => ['label' => 'Harga Beli', 'type' => 'money'],
                'markup_persen' => ['label' => 'Markup (%)', 'type' => 'percent'],
                'stok'         => ['label' => 'Stok Awal', 'type' => 'number', 'list' => true],
                'stok_minimal' => ['label' => 'Stok Minimal', 'type' => 'number'],
                'status'       => ['label' => 'Status', 'type' => 'enum', 'options' => ['aktif', 'nonaktif'], 'default' => 'aktif', 'list' => true],
            ],
        ],

        // ---------- Penjamin & Bank ----------
        'asuransi' => [
            'label' => 'Asuransi / BPJS', 'singular' => 'Asuransi',
            'table' => 'asuransi', 'group' => 'Penjamin & Bank', 'icon' => app_icon('shield'), 'order' => 'nama',
            'fields' => [
                'kode'     => ['label' => 'Kode', 'type' => 'text', 'required' => true, 'list' => true],
                'nama'     => ['label' => 'Nama Asuransi', 'type' => 'text', 'required' => true, 'list' => true],
                'jenis'    => ['label' => 'Jenis', 'type' => 'enum', 'options' => ['bpjs', 'swasta'], 'default' => 'swasta', 'list' => true],
                'provider' => ['label' => 'Provider', 'type' => 'text'],
                'telepon'  => ['label' => 'Telepon', 'type' => 'text'],
                'status'   => ['label' => 'Status', 'type' => 'enum', 'options' => ['aktif', 'nonaktif'], 'default' => 'aktif', 'list' => true],
            ],
        ],
        'corporate' => [
            'label' => 'Corporate / Perusahaan', 'singular' => 'Corporate',
            'table' => 'corporate', 'group' => 'Penjamin & Bank', 'icon' => app_icon('building'), 'order' => 'nama',
            'fields' => [
                'kode'          => ['label' => 'Kode', 'type' => 'text', 'required' => true, 'list' => true],
                'nama'          => ['label' => 'Nama Perusahaan', 'type' => 'text', 'required' => true, 'list' => true],
                'kontak'        => ['label' => 'Kontak', 'type' => 'text'],
                'telepon'       => ['label' => 'Telepon', 'type' => 'text'],
                'alamat'        => ['label' => 'Alamat', 'type' => 'textarea'],
                'limit_jaminan' => ['label' => 'Limit Jaminan', 'type' => 'money', 'list' => true],
                'syarat'        => ['label' => 'Syarat & Ketentuan', 'type' => 'textarea'],
                'status'        => ['label' => 'Status', 'type' => 'enum', 'options' => ['aktif', 'nonaktif'], 'default' => 'aktif', 'list' => true],
            ],
        ],
        'bank' => [
            'label' => 'Bank / Rekening', 'singular' => 'Bank',
            'table' => 'bank', 'group' => 'Penjamin & Bank', 'icon' => app_icon('bank'), 'order' => 'nama_bank',
            'fields' => [
                'nama_bank'   => ['label' => 'Nama Bank', 'type' => 'text', 'required' => true, 'list' => true],
                'no_rekening' => ['label' => 'No. Rekening', 'type' => 'text', 'required' => true, 'list' => true],
                'atas_nama'   => ['label' => 'Atas Nama', 'type' => 'text', 'required' => true, 'list' => true],
                'cabang'      => ['label' => 'Cabang', 'type' => 'text', 'list' => true],
                'status'      => ['label' => 'Status', 'type' => 'enum', 'options' => ['aktif', 'nonaktif'], 'default' => 'aktif', 'list' => true],
            ],
        ],

        // ---------- Billing ----------
        'kode_pembatalan' => [
            'label' => 'Kode Pembatalan Billing', 'singular' => 'Kode Pembatalan',
            'table' => 'kode_pembatalan', 'group' => 'Billing', 'icon' => app_icon('close'), 'order' => 'kode',
            'fields' => [
                'kode'       => ['label' => 'Kode', 'type' => 'text', 'required' => true, 'list' => true],
                'nama'       => ['label' => 'Nama Alasan', 'type' => 'text', 'required' => true, 'list' => true],
                'keterangan' => ['label' => 'Keterangan', 'type' => 'text', 'list' => true],
                'status'     => ['label' => 'Status', 'type' => 'enum', 'options' => ['aktif', 'nonaktif'], 'default' => 'aktif', 'list' => true],
            ],
        ],

        // ---------- Pasien ----------
        'kelompok_pasien' => [
            'label' => 'Kelompok Pasien', 'singular' => 'Kelompok Pasien',
            'table' => 'kelompok_pasien', 'group' => 'Pasien', 'icon' => app_icon('users'), 'order' => 'nama',
            'fields' => [
                'nama'       => ['label' => 'Nama Kelompok', 'type' => 'text', 'required' => true, 'list' => true],
                'keterangan' => ['label' => 'Keterangan', 'type' => 'text', 'list' => true],
            ],
        ],
    ];
}

/** Ambil konfigurasi satu entitas, atau null bila tidak ada */
function master_entity(string $slug): ?array
{
    $all = master_entities();
    if (!isset($all[$slug])) return null;
    $e = $all[$slug];
    $e['slug'] = $slug;
    return $e;
}

/** Cache map id=>label untuk field FK (untuk tampilan daftar & pilihan form) */
function fk_map(string $table, string $labelCol): array
{
    static $cache = [];
    $key = $table . '|' . $labelCol;
    if (!isset($cache[$key])) {
        $rows = db()->query("SELECT id, {$labelCol} AS lbl FROM {$table} ORDER BY {$labelCol}")->fetchAll();
        $map = [];
        foreach ($rows as $r) $map[$r['id']] = $r['lbl'];
        $cache[$key] = $map;
    }
    return $cache[$key];
}
