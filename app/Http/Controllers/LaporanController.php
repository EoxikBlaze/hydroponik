<?php

namespace App\Http\Controllers;

use App\Models\AlertLog;
use App\Models\Monitoring;
use App\Models\Tanaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        [$laporan, $total] = $this->hitung($request);

        return view('laporan.index', [
            'tanaman_list' => Tanaman::all(),
            'tahun_list' => $this->getTahunList(),
            'filter' => $this->filter($request),
            'laporan' => $laporan,
            'total' => $total,
            'sensor' => Monitoring::avg6Jam(),
            'alerts' => AlertLog::alert6Jam(),
        ]);
    }

    /** Unduh laporan yang sama sebagai CSV (dipakai Excel/LibreOffice). */
    public function csv(Request $request): StreamedResponse
    {
        [$laporan, $total] = $this->hitung($request);
        $nama = 'laporan-siklus-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($laporan, $total) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF");   // BOM supaya Excel membaca UTF-8
            fputcsv($out, ['TANAMAN', 'BATCH', 'MULAI SEMAI', 'SEMAI BERHASIL', 'SEMAI GAGAL',
                'PEREMAJAAN BERHASIL', 'PEREMAJAAN GAGAL', 'PENDEWASAAN BERHASIL', 'PENDEWASAAN GAGAL',
                'PANEN BERHASIL', 'PANEN GAGAL']);
            foreach ($laporan as $r) {
                fputcsv($out, [$r['nama_tanaman'], '#'.$r['batch'], $r['tgl'] ?? '',
                    $r['semai_berhasil'], $r['semai_gagal'],
                    $r['peremajaan_berhasil'], $r['peremajaan_gagal'],
                    $r['pendewasaan_berhasil'], $r['pendewasaan_gagal'],
                    $r['panen_berhasil'], $r['panen_gagal']]);
            }
            fputcsv($out, ['TOTAL KESELURUHAN', '', '',
                $total['semai_berhasil'], $total['semai_gagal'],
                $total['peremajaan_berhasil'], $total['peremajaan_gagal'],
                $total['pendewasaan_berhasil'], $total['pendewasaan_gagal'],
                $total['panen_berhasil'], $total['panen_gagal']]);
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Rekap PER BATCH — tiap baris = satu record semai (satu batch), sehingga
     * dua batch tanaman yang sama (bahkan berada di meja yang sama) tetap
     * terbedakan sebagai baris terpisah. Tiap tahap dijumlahkan dari rantai
     * milik batch tersebut: semai -> peremajaan -> pendewasaan -> panen.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, int>}
     */
    private function hitung(Request $request): array
    {
        $f = $this->filter($request);

        // Satu baris per batch: id_semai adalah pengenal batch.
        $batch = DB::table('semais as s')
            ->join('tanamen as t', 't.id_tanaman', '=', 's.id_tanaman')
            ->selectRaw('s.id_semai, s.id_tanaman, t.nama_tanaman, s.tgl_awal_semai')
            ->when($f['id_tanaman'], fn ($q) => $q->where('s.id_tanaman', $f['id_tanaman']))
            ->orderBy('t.nama_tanaman')->orderBy('s.id_semai')
            ->get();

        $total = array_fill_keys([
            'semai_berhasil', 'semai_gagal', 'peremajaan_berhasil', 'peremajaan_gagal',
            'pendewasaan_berhasil', 'pendewasaan_gagal', 'panen_berhasil', 'panen_gagal',
        ], 0);

        $laporan = [];
        foreach ($batch as $b) {
            $semai = $this->jumlah('semais', 'benih_berhasil', 'benih_gagal',
                fn ($q) => $q->where('id_semai', $b->id_semai),
                'tgl_awal_semai', $f);

            $per = $this->jumlah('peremajaans', 'benih_berhasil', 'benih_gagal',
                fn ($q) => $q->where('id_semai', $b->id_semai),
                'tgl_awal_peremajaan', $f);

            $pen = $this->jumlah('pendewasaans', 'tanaman_berhasil', 'tanaman_gagal',
                fn ($q) => $q->join('peremajaans as p', 'p.id_peremajaan', '=', 'pendewasaans.id_peremajaan')
                    ->where('p.id_semai', $b->id_semai),
                'tgl_awal_pendewasaan', $f);

            $pan = $this->jumlah('panens', 'panen_berhasil', 'panen_gagal',
                fn ($q) => $q->join('pendewasaans as d', 'd.id_pendewasaan', '=', 'panens.id_pendewasaan')
                    ->join('peremajaans as p', 'p.id_peremajaan', '=', 'd.id_peremajaan')
                    ->where('p.id_semai', $b->id_semai),
                'tgl_panen', $f);

            $row = [
                'batch' => (int) $b->id_semai,
                'tgl' => $b->tgl_awal_semai,
                'id_tanaman' => $b->id_tanaman,
                'nama_tanaman' => $b->nama_tanaman,
                'semai_berhasil' => $semai[0], 'semai_gagal' => $semai[1],
                'peremajaan_berhasil' => $per[0], 'peremajaan_gagal' => $per[1],
                'pendewasaan_berhasil' => $pen[0], 'pendewasaan_gagal' => $pen[1],
                'panen_berhasil' => $pan[0], 'panen_gagal' => $pan[1],
            ];
            foreach ($row as $k => $v) {
                // Hanya kolom metrik yang boleh masuk ke akumulator total.
                if (is_int($v) && array_key_exists($k, $total)) {
                    $total[$k] += $v;
                }
            }
            // Sembunyikan baris batch yang tidak punya data sama sekali pada periode ini
            // (seluruh hasil 0) supaya matriks tidak penuh angka 0 yang membingungkan.
            $adaIsi = ($row['semai_berhasil'] + $row['semai_gagal']
                + $row['peremajaan_berhasil'] + $row['peremajaan_gagal']
                + $row['pendewasaan_berhasil'] + $row['pendewasaan_gagal']
                + $row['panen_berhasil'] + $row['panen_gagal']) > 0;
            if ($adaIsi) {
                $laporan[] = $row;
            }
        }

        return [$laporan, $total];
    }

    /** @return array{0: int, 1: int} [berhasil, gagal] */
    private function jumlah(string $tabel, string $kolomB, string $kolomG, callable $scope, string $tgl, array $f): array
    {
        $q = DB::table($tabel)->selectRaw(
            "COALESCE(SUM($tabel.$kolomB),0) as berhasil, COALESCE(SUM($tabel.$kolomG),0) as gagal"
        );
        $scope($q);
        $this->applyPeriodeFilter($q, $tabel.'.'.$tgl, $f['periode'], $f['tahun'], $f['bulan'], $f['minggu']);
        $r = $q->first();

        return [(int) ($r->berhasil ?? 0), (int) ($r->gagal ?? 0)];
    }

    private function filter(Request $request): array
    {
        return [
            'id_tanaman' => $request->get('id_tanaman'),
            'periode' => $request->get('periode', 'bulan'),
            'tahun' => $request->get('tahun', date('Y')),
            'bulan' => $request->get('bulan', date('m')),
            'minggu' => $request->get('minggu', date('W')),
        ];
    }

    private function applyPeriodeFilter($builder, string $field, ?string $periode, $tahun, $bulan, $minggu): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        if ($periode === 'semua') {
            return;   // tanpa filter waktu
        }
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
            'semais' => 'tgl_awal_semai',
            'peremajaans' => 'tgl_awal_peremajaan',
            'pendewasaans' => 'tgl_awal_pendewasaan',
            'panens' => 'tgl_panen',
        ];

        foreach ($tables as $table => $col) {
            $expr = $isSqlite ? "DISTINCT strftime('%Y', $col) as thn" : "DISTINCT YEAR($col) as thn";
            $res = DB::table($table)->selectRaw($expr)->whereNotNull($col)->pluck('thn')->toArray();
            $years = array_merge($years, $res);
        }

        $years = array_filter(array_map('intval', array_unique($years)));
        if (! in_array((int) date('Y'), $years)) {
            $years[] = (int) date('Y');
        }
        rsort($years);

        return array_values($years);
    }
}
