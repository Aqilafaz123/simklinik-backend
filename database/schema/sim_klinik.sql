-- Skema SIM Klinik (dipakai oleh migration Laravel)

CREATE TABLE roles (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  kode        VARCHAR(30) NOT NULL UNIQUE,
  nama        VARCHAR(60) NOT NULL,
  keterangan  VARCHAR(150) NULL
) ENGINE=InnoDB;

CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  role_id     INT NOT NULL,
  poli_id     INT NULL,
  nama        VARCHAR(100) NOT NULL,
  username    VARCHAR(50) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  email       VARCHAR(100) NULL,
  telepon     VARCHAR(20) NULL,
  avatar      VARCHAR(255) NULL,
  status      ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  last_login  DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE kelompok_pasien (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  nama    VARCHAR(60) NOT NULL,
  keterangan VARCHAR(150) NULL
) ENGINE=InnoDB;

CREATE TABLE pasien (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  no_mr           VARCHAR(20) NOT NULL UNIQUE,
  nik             VARCHAR(20) NULL,
  no_kk           VARCHAR(20) NULL,
  nama            VARCHAR(120) NOT NULL,
  nama_ibu        VARCHAR(120) NULL,
  tempat_lahir    VARCHAR(60) NULL,
  tgl_lahir       DATE NULL,
  jenis_kelamin   ENUM('L','P') NOT NULL DEFAULT 'L',
  gol_darah       ENUM('A','B','AB','O','-') DEFAULT '-',
  agama           VARCHAR(20) NULL,
  status_kawin    VARCHAR(20) NULL,
  pendidikan      VARCHAR(30) NULL,
  kewarganegaraan VARCHAR(20) NULL DEFAULT 'WNI',
  alamat          TEXT NULL,
  rt_rw           VARCHAR(15) NULL,
  kelurahan       VARCHAR(60) NULL,
  kecamatan       VARCHAR(60) NULL,
  kota            VARCHAR(60) NULL,
  provinsi        VARCHAR(60) NULL,
  kode_pos        VARCHAR(10) NULL,
  telepon         VARCHAR(20) NULL,
  email           VARCHAR(80) NULL,
  pekerjaan       VARCHAR(60) NULL,
  kelompok_id     INT NULL,
  no_asuransi     VARCHAR(40) NULL,
  kontak_nama     VARCHAR(120) NULL,
  kontak_hubungan VARCHAR(40) NULL,
  kontak_telepon  VARCHAR(20) NULL,
  alergi          TEXT NULL,
  riwayat_penyakit TEXT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pasien_kelompok FOREIGN KEY (kelompok_id) REFERENCES kelompok_pasien(id)
) ENGINE=InnoDB;

CREATE TABLE spesialisasi (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  nama  VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE poli (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  kode    VARCHAR(15) NOT NULL UNIQUE,
  nama    VARCHAR(80) NOT NULL,
  status  ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB;

CREATE TABLE dokter (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  kode          VARCHAR(20) NOT NULL UNIQUE,
  nama          VARCHAR(120) NOT NULL,
  spesialisasi_id INT NULL,
  poli_id       INT NULL,
  no_sip        VARCHAR(40) NULL,
  telepon       VARCHAR(20) NULL,
  tarif_jasa    DECIMAL(12,2) NOT NULL DEFAULT 0,
  status        ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  user_id       INT NULL,
  CONSTRAINT fk_dokter_spesialisasi FOREIGN KEY (spesialisasi_id) REFERENCES spesialisasi(id),
  CONSTRAINT fk_dokter_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

ALTER TABLE users ADD CONSTRAINT fk_users_poli FOREIGN KEY (poli_id) REFERENCES poli(id);
ALTER TABLE dokter ADD CONSTRAINT fk_dokter_poli FOREIGN KEY (poli_id) REFERENCES poli(id);

CREATE TABLE jadwal_dokter (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  dokter_id INT NOT NULL,
  poli_id   INT NOT NULL,
  hari      ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
  jam_mulai TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  kuota     INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_jadwal_dokter FOREIGN KEY (dokter_id) REFERENCES dokter(id),
  CONSTRAINT fk_jadwal_poli FOREIGN KEY (poli_id) REFERENCES poli(id)
) ENGINE=InnoDB;

CREATE TABLE tindakan (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  kode      VARCHAR(20) NOT NULL UNIQUE,
  nama      VARCHAR(120) NOT NULL,
  kode_icd9 VARCHAR(15) NULL,
  tarif     DECIMAL(12,2) NOT NULL DEFAULT 0,
  status    ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB;

CREATE TABLE lab_kategori (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE lab_pemeriksaan (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  kategori_id INT NULL,
  kode        VARCHAR(20) NOT NULL UNIQUE,
  nama        VARCHAR(120) NOT NULL,
  satuan      VARCHAR(20) NULL,
  nilai_rujukan VARCHAR(60) NULL,
  tarif       DECIMAL(12,2) NOT NULL DEFAULT 0,
  status      ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  CONSTRAINT fk_lab_kategori FOREIGN KEY (kategori_id) REFERENCES lab_kategori(id)
) ENGINE=InnoDB;

CREATE TABLE rad_kategori (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE rad_pemeriksaan (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  kategori_id INT NULL,
  kode        VARCHAR(20) NOT NULL UNIQUE,
  nama        VARCHAR(120) NOT NULL,
  tarif       DECIMAL(12,2) NOT NULL DEFAULT 0,
  status      ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  CONSTRAINT fk_rad_kategori FOREIGN KEY (kategori_id) REFERENCES rad_kategori(id)
) ENGINE=InnoDB;

CREATE TABLE obat_kategori (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE obat_satuan (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(40) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE obat (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  kode         VARCHAR(20) NOT NULL UNIQUE,
  nama         VARCHAR(120) NOT NULL,
  kategori_id  INT NULL,
  satuan_id    INT NULL,
  harga_beli   DECIMAL(12,2) NOT NULL DEFAULT 0,
  harga_jual   DECIMAL(12,2) NOT NULL DEFAULT 0,
  stok         INT NOT NULL DEFAULT 0,
  stok_minimal INT NOT NULL DEFAULT 0,
  status       ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  CONSTRAINT fk_obat_kategori FOREIGN KEY (kategori_id) REFERENCES obat_kategori(id),
  CONSTRAINT fk_obat_satuan FOREIGN KEY (satuan_id) REFERENCES obat_satuan(id)
) ENGINE=InnoDB;

CREATE TABLE bank (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nama_bank   VARCHAR(60) NOT NULL,
  no_rekening VARCHAR(40) NOT NULL,
  atas_nama   VARCHAR(100) NOT NULL,
  cabang      VARCHAR(80) NULL,
  status      ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB;

CREATE TABLE asuransi (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  kode      VARCHAR(20) NOT NULL UNIQUE,
  nama      VARCHAR(120) NOT NULL,
  jenis     ENUM('bpjs','swasta') NOT NULL DEFAULT 'swasta',
  provider  VARCHAR(120) NULL,
  telepon   VARCHAR(20) NULL,
  status    ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB;

CREATE TABLE corporate (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  kode        VARCHAR(20) NOT NULL UNIQUE,
  nama        VARCHAR(120) NOT NULL,
  kontak      VARCHAR(100) NULL,
  telepon     VARCHAR(20) NULL,
  alamat      TEXT NULL,
  limit_jaminan DECIMAL(14,2) NOT NULL DEFAULT 0,
  syarat      TEXT NULL,
  status      ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB;

CREATE TABLE supplier (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  nama    VARCHAR(120) NOT NULL,
  kontak  VARCHAR(100) NULL,
  telepon VARCHAR(20) NULL,
  alamat  TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE obat_batch (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  obat_id     INT NOT NULL,
  no_batch    VARCHAR(40) NULL,
  tgl_expired DATE NULL,
  stok        INT NOT NULL DEFAULT 0,
  harga_beli  DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_batch_obat FOREIGN KEY (obat_id) REFERENCES obat(id)
) ENGINE=InnoDB;

CREATE TABLE pembelian (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  no_beli     VARCHAR(30) NOT NULL UNIQUE,
  supplier_id INT NULL,
  tanggal     DATE NOT NULL,
  total       DECIMAL(14,2) NOT NULL DEFAULT 0,
  keterangan  VARCHAR(200) NULL,
  user_id     INT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_beli_supplier FOREIGN KEY (supplier_id) REFERENCES supplier(id),
  CONSTRAINT fk_beli_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE pembelian_detail (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  pembelian_id INT NOT NULL,
  obat_id      INT NOT NULL,
  no_batch     VARCHAR(40) NULL,
  tgl_expired  DATE NULL,
  qty          INT NOT NULL DEFAULT 0,
  harga_beli   DECIMAL(12,2) NOT NULL DEFAULT 0,
  subtotal     DECIMAL(14,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_belidet_pembelian FOREIGN KEY (pembelian_id) REFERENCES pembelian(id),
  CONSTRAINT fk_belidet_obat FOREIGN KEY (obat_id) REFERENCES obat(id)
) ENGINE=InnoDB;

CREATE TABLE stok_mutasi (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  obat_id     INT NOT NULL,
  tanggal     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  jenis       ENUM('masuk','keluar','opname','penyesuaian') NOT NULL,
  qty         INT NOT NULL,
  stok_akhir  INT NOT NULL DEFAULT 0,
  ref_tabel   VARCHAR(40) NULL,
  ref_id      INT NULL,
  keterangan  VARCHAR(200) NULL,
  user_id     INT NULL,
  CONSTRAINT fk_mutasi_obat FOREIGN KEY (obat_id) REFERENCES obat(id)
) ENGINE=InnoDB;

CREATE TABLE kunjungan (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  no_kunjungan  VARCHAR(30) NOT NULL UNIQUE,
  pasien_id     INT NOT NULL,
  poli_id       INT NOT NULL,
  dokter_id     INT NULL,
  tgl_kunjungan DATE NOT NULL,
  no_antrian    INT NOT NULL DEFAULT 0,
  jenis_penjamin ENUM('umum','bpjs','asuransi','corporate') NOT NULL DEFAULT 'umum',
  asuransi_id   INT NULL,
  corporate_id  INT NULL,
  no_jaminan    VARCHAR(50) NULL,
  status        ENUM('menunggu','periksa','penunjang','farmasi','billing','pembayaran','selesai','batal') NOT NULL DEFAULT 'menunggu',
  keluhan_awal  VARCHAR(255) NULL,
  user_id       INT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_kj_pasien FOREIGN KEY (pasien_id) REFERENCES pasien(id),
  CONSTRAINT fk_kj_poli FOREIGN KEY (poli_id) REFERENCES poli(id),
  CONSTRAINT fk_kj_dokter FOREIGN KEY (dokter_id) REFERENCES dokter(id),
  CONSTRAINT fk_kj_asuransi FOREIGN KEY (asuransi_id) REFERENCES asuransi(id),
  CONSTRAINT fk_kj_corporate FOREIGN KEY (corporate_id) REFERENCES corporate(id),
  CONSTRAINT fk_kj_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE rekam_medis (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  kunjungan_id INT NOT NULL UNIQUE,
  dokter_id    INT NULL,
  subjective   TEXT NULL,
  objective    TEXT NULL,
  assessment   TEXT NULL,
  plan         TEXT NULL,
  edukasi      TEXT NULL,
  tekanan_darah VARCHAR(15) NULL,
  suhu         VARCHAR(10) NULL,
  nadi         VARCHAR(10) NULL,
  berat_badan  VARCHAR(10) NULL,
  tinggi_badan VARCHAR(10) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rm_kunjungan FOREIGN KEY (kunjungan_id) REFERENCES kunjungan(id),
  CONSTRAINT fk_rm_dokter FOREIGN KEY (dokter_id) REFERENCES dokter(id)
) ENGINE=InnoDB;

CREATE TABLE rm_diagnosa (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  rekam_medis_id INT NOT NULL,
  kode_icd10    VARCHAR(15) NULL,
  diagnosa      VARCHAR(200) NOT NULL,
  jenis         ENUM('primer','sekunder') NOT NULL DEFAULT 'primer',
  CONSTRAINT fk_diag_rm FOREIGN KEY (rekam_medis_id) REFERENCES rekam_medis(id)
) ENGINE=InnoDB;

CREATE TABLE rm_tindakan (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  rekam_medis_id INT NOT NULL,
  tindakan_id   INT NULL,
  nama_tindakan VARCHAR(150) NOT NULL,
  qty           INT NOT NULL DEFAULT 1,
  tarif         DECIMAL(12,2) NOT NULL DEFAULT 0,
  subtotal      DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_rmtind_rm FOREIGN KEY (rekam_medis_id) REFERENCES rekam_medis(id),
  CONSTRAINT fk_rmtind_tindakan FOREIGN KEY (tindakan_id) REFERENCES tindakan(id)
) ENGINE=InnoDB;

CREATE TABLE lab_order (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  kunjungan_id INT NOT NULL,
  tanggal      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status       ENUM('permintaan','proses','selesai') NOT NULL DEFAULT 'permintaan',
  CONSTRAINT fk_laborder_kj FOREIGN KEY (kunjungan_id) REFERENCES kunjungan(id)
) ENGINE=InnoDB;

CREATE TABLE lab_order_detail (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  lab_order_id INT NOT NULL,
  pemeriksaan_id INT NOT NULL,
  hasil        VARCHAR(120) NULL,
  nilai_rujukan VARCHAR(60) NULL,
  tarif        DECIMAL(12,2) NOT NULL DEFAULT 0,
  qty          INT NOT NULL DEFAULT 1,
  subtotal     DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_labdet_order FOREIGN KEY (lab_order_id) REFERENCES lab_order(id),
  CONSTRAINT fk_labdet_pem FOREIGN KEY (pemeriksaan_id) REFERENCES lab_pemeriksaan(id)
) ENGINE=InnoDB;

CREATE TABLE rad_order (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  kunjungan_id INT NOT NULL,
  tanggal      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status       ENUM('permintaan','proses','selesai') NOT NULL DEFAULT 'permintaan',
  CONSTRAINT fk_radorder_kj FOREIGN KEY (kunjungan_id) REFERENCES kunjungan(id)
) ENGINE=InnoDB;

CREATE TABLE rad_order_detail (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  rad_order_id INT NOT NULL,
  pemeriksaan_id INT NOT NULL,
  hasil        TEXT NULL,
  tarif        DECIMAL(12,2) NOT NULL DEFAULT 0,
  qty          INT NOT NULL DEFAULT 1,
  subtotal     DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_raddet_order FOREIGN KEY (rad_order_id) REFERENCES rad_order(id),
  CONSTRAINT fk_raddet_pem FOREIGN KEY (pemeriksaan_id) REFERENCES rad_pemeriksaan(id)
) ENGINE=InnoDB;

CREATE TABLE diag_kategori (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE diag_pemeriksaan (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  kategori_id INT NULL,
  kode        VARCHAR(20) NOT NULL UNIQUE,
  nama        VARCHAR(120) NOT NULL,
  tarif       DECIMAL(12,2) NOT NULL DEFAULT 0,
  status      ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  CONSTRAINT fk_diag_kategori FOREIGN KEY (kategori_id) REFERENCES diag_kategori(id)
) ENGINE=InnoDB;

CREATE TABLE diag_order (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  kunjungan_id INT NOT NULL,
  tanggal      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status       ENUM('permintaan','proses','selesai') NOT NULL DEFAULT 'permintaan',
  CONSTRAINT fk_diagorder_kj FOREIGN KEY (kunjungan_id) REFERENCES kunjungan(id)
) ENGINE=InnoDB;

CREATE TABLE diag_order_detail (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  diag_order_id  INT NOT NULL,
  pemeriksaan_id INT NOT NULL,
  hasil          TEXT NULL,
  tarif          DECIMAL(12,2) NOT NULL DEFAULT 0,
  qty            INT NOT NULL DEFAULT 1,
  subtotal       DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_diagdet_order FOREIGN KEY (diag_order_id) REFERENCES diag_order(id),
  CONSTRAINT fk_diagdet_pem FOREIGN KEY (pemeriksaan_id) REFERENCES diag_pemeriksaan(id)
) ENGINE=InnoDB;

CREATE TABLE fisio_kategori (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE fisio_pemeriksaan (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  kategori_id INT NULL,
  kode        VARCHAR(20) NOT NULL UNIQUE,
  nama        VARCHAR(120) NOT NULL,
  tarif       DECIMAL(12,2) NOT NULL DEFAULT 0,
  status      ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  CONSTRAINT fk_fisio_kategori FOREIGN KEY (kategori_id) REFERENCES fisio_kategori(id)
) ENGINE=InnoDB;

CREATE TABLE fisio_order (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  kunjungan_id INT NOT NULL,
  tanggal      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status       ENUM('permintaan','proses','selesai') NOT NULL DEFAULT 'permintaan',
  CONSTRAINT fk_fisioorder_kj FOREIGN KEY (kunjungan_id) REFERENCES kunjungan(id)
) ENGINE=InnoDB;

CREATE TABLE fisio_order_detail (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  fisio_order_id INT NOT NULL,
  pemeriksaan_id INT NOT NULL,
  hasil          TEXT NULL,
  tarif          DECIMAL(12,2) NOT NULL DEFAULT 0,
  qty            INT NOT NULL DEFAULT 1,
  subtotal       DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_fisiodet_order FOREIGN KEY (fisio_order_id) REFERENCES fisio_order(id),
  CONSTRAINT fk_fisiodet_pem FOREIGN KEY (pemeriksaan_id) REFERENCES fisio_pemeriksaan(id)
) ENGINE=InnoDB;

CREATE TABLE resep (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  kunjungan_id INT NOT NULL,
  dokter_id    INT NULL,
  tanggal      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status       ENUM('baru','disiapkan','diserahkan') NOT NULL DEFAULT 'baru',
  catatan      VARCHAR(200) NULL,
  CONSTRAINT fk_resep_kj FOREIGN KEY (kunjungan_id) REFERENCES kunjungan(id),
  CONSTRAINT fk_resep_dokter FOREIGN KEY (dokter_id) REFERENCES dokter(id)
) ENGINE=InnoDB;

CREATE TABLE resep_detail (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  resep_id    INT NOT NULL,
  obat_id     INT NOT NULL,
  qty         INT NOT NULL DEFAULT 1,
  dosis       VARCHAR(60) NULL,
  aturan_pakai VARCHAR(120) NULL,
  harga       DECIMAL(12,2) NOT NULL DEFAULT 0,
  subtotal    DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_resepdet_resep FOREIGN KEY (resep_id) REFERENCES resep(id),
  CONSTRAINT fk_resepdet_obat FOREIGN KEY (obat_id) REFERENCES obat(id)
) ENGINE=InnoDB;

CREATE TABLE billing (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  kunjungan_id INT NOT NULL UNIQUE,
  subtotal     DECIMAL(14,2) NOT NULL DEFAULT 0,
  diskon       DECIMAL(14,2) NOT NULL DEFAULT 0,
  total        DECIMAL(14,2) NOT NULL DEFAULT 0,
  status       ENUM('draft','final') NOT NULL DEFAULT 'draft',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_billing_kj FOREIGN KEY (kunjungan_id) REFERENCES kunjungan(id)
) ENGINE=InnoDB;

CREATE TABLE billing_detail (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  billing_id  INT NOT NULL,
  kategori    ENUM('jasa_dokter','tindakan','laboratorium','radiologi','diagnostik','fisioterapi','farmasi','administrasi') NOT NULL,
  item_code   VARCHAR(30) NULL,
  deskripsi   VARCHAR(150) NOT NULL,
  qty         INT NOT NULL DEFAULT 1,
  tarif       DECIMAL(12,2) NOT NULL DEFAULT 0,
  subtotal    DECIMAL(14,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_billdet_billing FOREIGN KEY (billing_id) REFERENCES billing(id)
) ENGINE=InnoDB;

CREATE TABLE invoice (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  no_invoice  VARCHAR(30) NOT NULL UNIQUE,
  billing_id  INT NOT NULL,
  kunjungan_id INT NOT NULL,
  tanggal     DATE NOT NULL,
  total       DECIMAL(14,2) NOT NULL DEFAULT 0,
  terbayar    DECIMAL(14,2) NOT NULL DEFAULT 0,
  status      ENUM('belum_bayar','sebagian','lunas') NOT NULL DEFAULT 'belum_bayar',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_inv_billing FOREIGN KEY (billing_id) REFERENCES billing(id),
  CONSTRAINT fk_inv_kj FOREIGN KEY (kunjungan_id) REFERENCES kunjungan(id)
) ENGINE=InnoDB;

CREATE TABLE pembayaran (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id  INT NOT NULL,
  tanggal     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  metode      ENUM('cash','transfer','qris','edc','va','ewallet','penjamin') NOT NULL DEFAULT 'cash',
  bank_id     INT NULL,
  jumlah      DECIMAL(14,2) NOT NULL DEFAULT 0,
  bukti       VARCHAR(255) NULL,
  status      ENUM('pending','valid','tolak') NOT NULL DEFAULT 'valid',
  keterangan  VARCHAR(200) NULL,
  user_id     INT NULL,
  CONSTRAINT fk_bayar_invoice FOREIGN KEY (invoice_id) REFERENCES invoice(id),
  CONSTRAINT fk_bayar_bank FOREIGN KEY (bank_id) REFERENCES bank(id),
  CONSTRAINT fk_bayar_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE setting (
  k VARCHAR(50) PRIMARY KEY,
  v TEXT NULL
) ENGINE=InnoDB;
