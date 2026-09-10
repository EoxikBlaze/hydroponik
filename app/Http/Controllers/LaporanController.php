<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{Tanaman, Semai, Peremajaan, Pendewasaan, Panen, Monitoring, AlertLog};

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $tanamanList = Tanaman::all();

        $idTanaman = $request->get('id_tanaman');
        $periode   = $request->get('periode', 'bulan');
        $tahun     = $request->get('tahun', date('Y'));
        $bulan     = $request->get('bulan', date('m'));
        $minggu    = $request->get('minggu', date('W'));

        // Query daftar tahun
        $tahunList = $this->getTahunList();
        if (empty($tahunList)) {
            $tahunList = [(int) date('Y')];
        }

        // Hitung Laporan Siklus per Tanaman
        $queryTanaman = Tanaman::query();
        if ($idTanaman) {
            $queryTanaman->where('id_tanaman', $idTanaman);
        }
        $targetTanamans = $queryTanaman->get();

        $laporan = [];
        $total = [
            'semai_berhasil'       => 0,
            'semai_gagal'          => 0,
            'peremajaan_berhasil'  => 0,
            'peremajaan_gagal'     => 0,
            'pendewasaan_berhasil' => 0,
            'pendewasaan_gagal'    => 0,
            'panen_berhasil'       => 0,
            'panen_gagal'          => 0,
        ];

        foreach ($targetTanamans as $t) {
            // SEMAI
            $qSemai = DB::table('semais')
                ->selectRaw('COALESCE(SUM(benih_berhasil),0) as berhasil, COALESCE(SUM(benih_gagal),0) as gagal')
                ->where('id_tanaman', $t->id_tanaman);
            $this->applyPeriodeFilter($qSemai, 'tgl_awal_semai', $periode, $tahun, $bulan, $minggu);
            $semaiData = $qSemai->first();

            // PEREMAJAAN
            $qPeremajaan = DB::table('peremajaans as p')
                ->selectRaw('COALESCE(SUM(p.benih_berhasil),0) as berhasil, COALESCE(SUM(p.benih_gagal),0) as gagal')
                ->join('semais as s', 's.id_semai', '=', 'p.id_semai')
                ->where('s.id_tanaman', $t->id_tanaman);
            $this->applyPeriodeFilter($qPeremajaan, 'p.tgl_awal_peremajaan', $periode, $tahun, $bulan, $minggu);
            $peremajaanData = $qPeremajaan->first();

            // PENDEWASAAN
            $qPendewasaan = DB::table('pendewasaans as d')
                ->selectRaw('COALESCE(SUM(d.tanaman_berhasil),0) as berhasil, COALESCE(SUM(d.tanaman_gagal),0) as gagal')
                ->join('peremajaans as p', 'p.id_peremajaan', '=', 'd.id_peremajaan')
                ->join('semais as s', 's.id_semai', '=', 'p.id_semai')
                ->where('s.id_tanaman', $t->id_tanaman);
            $this->applyPeriodeFilter($qPendewasaan, 'd.tgl_awal_pendewasaan', $periode, $tahun, $bulan, $minggu);
            $pendewasaanData = $qPendewasaan->first();

            // PANEN
            $qPanen = DB::table('panens as pan')
                ->selectRaw('COALESCE(SUM(pan.panen_berhasil),0) as berhasil, COALESCE(SUM(pan.panen_gagal),0) as gagal')
                ->join('pendewasaans as d', 'd.id_pendewasaan', '=', 'pan.id_pendewasaan')
                ->join('peremajaans as p', 'p.id_peremajaan', '=', 'd.id_peremajaan')
                ->join('semais as s', 's.id_semai', '=', 'p.id_semai')
                ->where('s.id_tanaman', $t->id_tanaman);
            $this->applyPeriodeFilter($qPanen, 'pan.tgl_panen', $periode, $tahun, $bulan, $minggu);
            $panenData = $qPanen->first();

            $row = [
                'id_tanaman'           => $t->id_tanaman,
                'nama_tanaman'         => $t->nama_tanaman,
                'semai_berhasil'       => (int) ($semaiData->berhasil ?? 0),
                'semai_gagal'          => (int) ($semaiData->gagal ?? 0),
                'peremajaan_berhasil'  => (int) ($peremajaanData->berhasil ?? 0),
                'peremajaan_gagal'     => (int) ($peremajaanData->gagal ?? 0),
                'pendewasaan_berhasil' => (int) ($pendewasaanData->berhasil ?? 0),
                'pendewasaan_gagal'    => (int) ($pendewasaanData->gagal ?? 0),
                'panen_berhasil'       => (int) ($panenData->berhasil ?? 0),
                'panen_gagal'          => (int) ($panenData->gagal ?? 0),
            ];

            $total['semai_berhasil']       += $row['semai_berhasil'];
            $total['semai_gagal']          += $row['semai_gagal'];
            $total['peremajaan_berhasil']  += $row['peremajaan_berhasil'];
            $total['peremajaan_gagal']     += $row['peremajaan_gagal'];
            $total['pendewasaan_berhasil'] += $row['pendewasaan_berhasil'];
            $total['pendewasaan_gagal']    += $row['pendewasaan_gagal'];
            $total['panen_berhasil']       += $row['panen_berhasil'];
            $total['panen_gagal']          += $row['panen_gagal'];

            $laporan[] = $row;
        }

        return view('laporan.index', [
            'tanaman_list' => $tanamanList,
            'tahun_list'   => $tahunList,
            'filter'       => [
                'id_tanaman' => $idTanaman,
                'periode'    => $periode,
                'tahun'      => $tahun,
                'bulan'      => $bulan,
                'minggu'     => $minggu,
            ],
            'laporan'      => $laporan,
            'total'        => $total,
            'sensor'       => Monitoring::avg6Jam(),
            'alerts'       => AlertLog::alert6Jam(),
        ]);
    }

    private function applyPeriodeFilter($builder, $field, $periode, $tahun, $bulan, $minggu)
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        if ($periode === 'tahun' && $tahun) {
            $builder->whereYear($field, $tahun);
        } elseif ($periode === 'bulan' && $tahun && $bulan) {
            $builder->whereYear($field, $tahun)->whereMonth($field, $bulan);
        } elseif ($periode === 'minggu' && $tahun && $minggu) {
            if ($isSqlite) {
                $builder->whereYear($field, $tahun)->whereRaw("CAST(strftime('%W', $field) AS INTEGER) = ?", [(int) $minggu]);
            } else {
                $builder->whereYear($field, $tahun)->whereRaw("WEEK($field, 1) = ?", [(int) $minggu]);
            }
        }
    }

    private function getTahunList(): array
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $years = [];
        $tables = [
            'semais'        => 'tgl_awal_semai',
            'peremajaans'   => 'tgl_awal_peremajaan',
            'pendewasaans'  => 'tgl_awal_pendewasaan',
            'panens'        => 'tgl_panen',
        ];

        foreach ($tables as $table => $col) {
            $expr = $isSqlite ? "DISTINCT strftime('%Y', $col) as thn" : "DISTINCT YEAR($col) as thn";
            $res  = DB::table($table)->selectRaw($expr)->whereNotNull($col)->pluck('thn')->toArray();
            $years = array_merge($years, $res);
        }

        $years = array_filter(array_map('intval', array_unique($years)));
        if (!in_array((int)date('Y'), $years)) {
            $years[] = (int)date('Y');
        }
        rsort($years);
        return array_values($years);
    }
}
