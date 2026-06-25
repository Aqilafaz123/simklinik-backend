<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $role = auth()->user()->roleKode();

        $canPatientData = in_array($role, ['superadmin', 'admin', 'registrasi'], true);
        $pasienTerbaru = $canPatientData
            ? DB::table('pasien as p')
                ->leftJoin('kelompok_pasien as kp', 'kp.id', '=', 'p.kelompok_id')
                ->select(
                    'p.id',
                    'p.no_mr',
                    'p.nama',
                    'p.jenis_kelamin',
                    'p.tgl_lahir',
                    'p.telepon',
                    'kp.nama as kelompok',
                )
                ->orderByDesc('p.id')
                ->limit(10)
                ->get()
            : collect();

        return view('dashboard.index', compact(
            'canPatientData',
            'pasienTerbaru',
        ));
    }
}
