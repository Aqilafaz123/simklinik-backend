<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kode_pembatalan')) {
            Schema::create('kode_pembatalan', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 20)->unique();
                $table->string('nama', 100);
                $table->string('keterangan', 255)->nullable();
                $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            });
        }

        Schema::table('kunjungan', function (Blueprint $table) {
            if (! Schema::hasColumn('kunjungan', 'kode_pembatalan_id')) {
                $table->unsignedBigInteger('kode_pembatalan_id')->nullable()->after('keluhan_awal');
            }
            if (! Schema::hasColumn('kunjungan', 'alasan_batal')) {
                $table->string('alasan_batal', 255)->nullable()->after('kode_pembatalan_id');
            }
            if (! Schema::hasColumn('kunjungan', 'batal_at')) {
                $table->dateTime('batal_at')->nullable()->after('alasan_batal');
            }
            if (! Schema::hasColumn('kunjungan', 'batal_by')) {
                $table->unsignedBigInteger('batal_by')->nullable()->after('batal_at');
            }
        });

        DB::table('roles')->updateOrInsert(
            ['kode' => 'superadmin'],
            ['nama' => 'Super Administrator', 'keterangan' => 'Akses penuh: rekam medis, master data, inventory & pengaturan']
        );

        DB::table('roles')->where('kode', 'admin')->update([
            'nama' => 'Admin',
            'keterangan' => 'Data pasien, pendaftaran, billing, keuangan & laporan',
        ]);

        if (DB::table('kode_pembatalan')->count() === 0) {
            DB::table('kode_pembatalan')->insert([
                ['kode' => 'BTL01', 'nama' => 'Pasien tidak jadi berobat', 'keterangan' => 'Pasien membatalkan kunjungan', 'status' => 'aktif'],
                ['kode' => 'BTL02', 'nama' => 'Salah input data', 'keterangan' => 'Kesalahan pendaftaran atau billing', 'status' => 'aktif'],
                ['kode' => 'BTL03', 'nama' => 'Duplikat tagihan', 'keterangan' => 'Tagihan ganda untuk kunjungan yang sama', 'status' => 'aktif'],
                ['kode' => 'BTL04', 'nama' => 'Lainnya', 'keterangan' => 'Alasan lain (isi keterangan)', 'status' => 'aktif'],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('kunjungan', function (Blueprint $table) {
            foreach (['kode_pembatalan_id', 'alasan_batal', 'batal_at', 'batal_by'] as $col) {
                if (Schema::hasColumn('kunjungan', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('kode_pembatalan');

        DB::table('roles')->where('kode', 'superadmin')->delete();

        DB::table('roles')->where('kode', 'admin')->update([
            'nama' => 'Administrator',
            'keterangan' => 'Akses penuh seluruh sistem',
        ]);
    }
};
