<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();
        $user = auth()->user();
        $role = $user->roleKode();
        $poliId = $role === 'dokter' ? $user->poli_id : null;

        $kunjunganQuery = DB::table('kunjungan as k');
        if ($poliId) {
            $kunjunganQuery->where('k.poli_id', $poliId);
        }

        $pasienHariIni = (clone $kunjunganQuery)->where('k.tgl_kunjungan', $today)->count();
        $antrianAktif = (clone $kunjunganQuery)
            ->where('k.tgl_kunjungan', $today)
            ->whereIn('k.status', ['menunggu', 'periksa', 'penunjang'])
            ->count();

        $totalPasien = DB::table('pasien')->count();
        $pendapatanHariIni = DB::table('pembayaran')
            ->whereDate('tanggal', $today)
            ->where('status', 'valid')
            ->sum('jumlah');
        $stokMenipis = DB::table('obat')
            ->whereColumn('stok', '<=', 'stok_minimal')
            ->where('status', 'aktif')
            ->count();

        $antrianQuery = DB::table('kunjungan as k')
            ->join('pasien as p', 'p.id', '=', 'k.pasien_id')
            ->join('poli as po', 'po.id', '=', 'k.poli_id')
            ->leftJoin('dokter as d', 'd.id', '=', 'k.dokter_id')
            ->where('k.tgl_kunjungan', $today)
            ->select('k.no_antrian', 'k.status', 'p.nama as pasien', 'po.nama as poli', 'd.nama as dokter')
            ->orderByDesc('k.no_antrian')
            ->limit(10);

        if ($poliId) {
            $antrianQuery->where('k.poli_id', $poliId);
        }

        $antrian = $antrianQuery->get();

        $badgeMap = [
            'menunggu' => 'badge-orange',
            'periksa' => 'badge-blue',
            'penunjang' => 'badge-blue',
            'farmasi' => 'badge-blue',
            'billing' => 'badge-blue',
            'pembayaran' => 'badge-orange',
            'selesai' => 'badge-green',
            'batal' => 'badge-red',
        ];

        return view('dashboard.index', compact(
            'pasienHariIni',
            'antrianAktif',
            'totalPasien',
            'pendapatanHariIni',
            'stokMenipis',
            'antrian',
            'badgeMap',
        ));
    }
}
