<?php

namespace App\Http\Controllers;

use App\Models\Meja;
use App\Models\Panen;
use App\Models\Pendewasaan;
use App\Models\Peremajaan;
use App\Models\Semai;
use App\Models\Siklus;
use App\Models\Tanaman;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * "Data Tahap (Detail)" — satu halaman yang menampilkan SELURUH tahap siklus
 * (Semai, Peremajaan, Pendewasaan, Panen) lewat tab, lengkap dengan pagination
 * baris-per-halaman supaya data menumpuk tidak memenuhi layar.
 *
 * SATU BARIS = SATU BATCH (id_semai). Bila satu tahap dipecah ke beberapa meja
 * (split 400 -> 200 + 200), baris-barisnya DIGABUNG: tanggal & jumlah dijumlah,
 * nama meja digabung, dan tiap sub-baris tetap punya tombol aksinya sendiri.
 * Tanpa penggabungan ini satu batch tampil sebagai banyak baris sehingga
 * terbaca sebagai banyak batch padahal id_semai-nya cuma satu.
 *
 * Route index tahap lain (peremajaan/pendewasaan/panen) di-redirect ke sini
 * agar menu di sidebar menjadi satu-satunya pintu masuk.
 */
class SemaiController extends Controller
{
    /** Definisi 4 tahap: kolom hasil, tanggal, relasi, dan route aksi. */
    private const TAHAP = ['semai', 'peremajaan', 'pendewasaan', 'panen'];

    private const LABEL = [
        'semai' => 'Semai', 'peremajaan' => 'Peremajaan',
        'pendewasaan' => 'Pendewasaan', 'panen' => 'Panen',
    ];

    public function index(Request $request)
    {
        $tahap = in_array($request->get('tahap'), self::TAHAP, true) ? $request->get('tahap') : 'semai';
        $q = trim((string) $request->get('q'));
        $dari = $request->get('dari');
        $sampai = $request->get('sampai');
        $status = $request->get('status');
        $perPage = (int) $request->get('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $rows = $this->queryTahap($tahap, $q, $dari, $sampai, $status)
            ->paginate($perPage)
            ->withQueryString();

        // Sub-baris per batch (untuk nama meja & tombol aksi). Dimuat hanya
        // untuk baris di halaman aktif agar hemat query.
        $anak = $tahap === 'semai'
            ? collect()
            : $this->muatAnak($tahap, $rows->getCollection()->pluck('batch')->all());

        return view('semai.index', [
            'tahapAktif' => $tahap,
            'daftarTahap' => self::TAHAP,
            'labelTahap' => self::LABEL,
            'rows' => $rows,
            'anak' => $anak,
            'perPageSize' => $perPage,
            // dipertahankan agar partial/create yang masih memakai $semais tidak error
            'semais' => collect(),
        ]);
    }

    /**
     * Sub-baris suatu batch pada tahap berikutnya, dikelompokkan per batch.
     *
     * @param  array<int>  $batchIds
     * @return Collection<int, \Illuminate\Database\Eloquent\Collection>
     */
    private function muatAnak(string $tahap, array $batchIds)
    {
        if ($batchIds === []) {
            return collect();
        }

        return match ($tahap) {
            'peremajaan' => Peremajaan::with('meja')
                ->whereIn('id_semai', $batchIds)
                ->orderBy('id_peremajaan')
                ->get()
                ->groupBy('id_semai'),

            // whereInHas() tidak ada di Laravel -> pakai rantai relasi biasa.
            'pendewasaan' => Pendewasaan::with(['meja', 'peremajaan.semai'])
                ->get()
                ->filter(fn ($d) => in_array($d->peremajaan?->id_semai, $batchIds, true))
                ->sortBy('id_pendewasaan')
                ->values()
                ->groupBy(fn ($d) => $d->peremajaan?->id_semai),

            default => Panen::with(['pendewasaan.meja', 'pendewasaan.peremajaan.semai'])
                ->get()
                ->filter(fn ($p) => in_array($p->pendewasaan?->peremajaan?->id_semai, $batchIds, true))
                ->sortBy('id_panen')
                ->values()
                ->groupBy(fn ($p) => $p->pendewasaan?->peremajaan?->id_semai),
        };
    }

    /** @return Builder */
    private function queryTahap(string $tahap, string $q, ?string $dari, ?string $sampai, ?string $status)
    {
        return match ($tahap) {
            'semai' => $this->qSemai($q, $dari, $sampai, $status),
            'peremajaan' => $this->qPeremajaan($q, $dari, $sampai),
            'pendewasaan' => $this->qPendewasaan($q, $dari, $sampai),
            default => $this->qPanen($q, $dari, $sampai),
        };
    }

    private function qSemai(string $q, ?string $dari, ?string $sampai, ?string $status)
    {
        return Semai::with(['tanaman', 'siklus', 'meja'])
            ->when($q, fn ($w) => $w->where(function ($x) use ($q) {
                $x->whereHas('tanaman', fn ($t) => $t->where('nama_tanaman', 'like', "%{$q}%"))
                    ->orWhere('keterangan', 'like', "%{$q}%");
            }))
            ->when($status, fn ($w) => $w->where('status', $status))
            ->when($dari, fn ($w) => $w->whereDate('tgl_awal_semai', '>=', $dari))
            ->when($sampai, fn ($w) => $w->whereDate('tgl_awal_semai', '<=', $sampai))
            ->latest('id_semai');
    }

    /**
     * Dikelompokkan PER BATCH (id_semai) — 1 baris mewakili seluruh meja tujuan.
     * Kolom `batch` dipakai view untuk memuat sub-baris (nama meja + aksi).
     *
     * @return Builder<int, object>
     */
    private function qPeremajaan(string $q, ?string $dari, ?string $sampai)
    {
        return Peremajaan::query()
            ->select('peremajaans.id_semai as batch')
            ->addSelect('tanamen.nama_tanaman')
            ->selectRaw('MIN(peremajaans.tgl_awal_peremajaan) as tgl1')
            ->selectRaw('MAX(peremajaans.tgl_akhir_peremajaan) as tgl2')
            ->selectRaw('SUM(peremajaans.benih_berhasil) as berhasil')
            ->selectRaw('SUM(peremajaans.benih_gagal) as gagal')
            ->selectRaw('COUNT(peremajaans.id_peremajaan) as jml_baris')
            ->selectRaw('MAX(peremajaans.keterangan) as keterangan')
            ->join('semais', 'semais.id_semai', '=', 'peremajaans.id_semai')
            ->join('tanamen', 'tanamen.id_tanaman', '=', 'semais.id_tanaman')
            ->when($q, fn ($w) => $w->where(function ($x) use ($q) {
                $x->where('tanamen.nama_tanaman', 'like', "%{$q}%")
                    ->orWhere('peremajaans.keterangan', 'like', "%{$q}%");
            }))
            ->when($dari, fn ($w) => $w->whereDate('peremajaans.tgl_awal_peremajaan', '>=', $dari))
            ->when($sampai, fn ($w) => $w->whereDate('peremajaans.tgl_awal_peremajaan', '<=', $sampai))
            ->groupBy('peremajaans.id_semai', 'tanamen.nama_tanaman')
            ->orderByDesc('peremajaans.id_semai');
    }

    /** @return Builder<int, object> */
    private function qPendewasaan(string $q, ?string $dari, ?string $sampai)
    {
        return Pendewasaan::query()
            ->select('semais.id_semai as batch')
            ->addSelect('tanamen.nama_tanaman')
            ->selectRaw('MIN(pendewasaans.tgl_awal_pendewasaan) as tgl1')
            ->selectRaw('MAX(pendewasaans.tgl_akhir_pendewasaan) as tgl2')
            ->selectRaw('SUM(pendewasaans.tanaman_berhasil) as berhasil')
            ->selectRaw('SUM(pendewasaans.tanaman_gagal) as gagal')
            ->selectRaw('COUNT(pendewasaans.id_pendewasaan) as jml_baris')
            ->selectRaw('MAX(pendewasaans.keterangan) as keterangan')
            ->join('peremajaans', 'peremajaans.id_peremajaan', '=', 'pendewasaans.id_peremajaan')
            ->join('semais', 'semais.id_semai', '=', 'peremajaans.id_semai')
            ->join('tanamen', 'tanamen.id_tanaman', '=', 'semais.id_tanaman')
            ->when($q, fn ($w) => $w->where(function ($x) use ($q) {
                $x->where('tanamen.nama_tanaman', 'like', "%{$q}%")
                    ->orWhere('pendewasaans.keterangan', 'like', "%{$q}%");
            }))
            ->when($dari, fn ($w) => $w->whereDate('pendewasaans.tgl_awal_pendewasaan', '>=', $dari))
            ->when($sampai, fn ($w) => $w->whereDate('pendewasaans.tgl_awal_pendewasaan', '<=', $sampai))
            ->groupBy('semais.id_semai', 'tanamen.nama_tanaman')
            ->orderByDesc('semais.id_semai');
    }

    /** @return Builder<int, object> */
    private function qPanen(string $q, ?string $dari, ?string $sampai)
    {
        return Panen::query()
            ->select('semais.id_semai as batch')
            ->addSelect('tanamen.nama_tanaman')
            ->selectRaw('MIN(panens.tgl_panen) as tgl1')
            ->selectRaw('MAX(panens.tgl_panen) as tgl2')
            ->selectRaw('SUM(panens.panen_berhasil) as berhasil')
            ->selectRaw('SUM(panens.panen_gagal) as gagal')
            ->selectRaw('COUNT(panens.id_panen) as jml_baris')
            ->selectRaw('MAX(panens.keterangan) as keterangan')
            ->join('pendewasaans', 'pendewasaans.id_pendewasaan', '=', 'panens.id_pendewasaan')
            ->join('peremajaans', 'peremajaans.id_peremajaan', '=', 'pendewasaans.id_peremajaan')
            ->join('semais', 'semais.id_semai', '=', 'peremajaans.id_semai')
            ->join('tanamen', 'tanamen.id_tanaman', '=', 'semais.id_tanaman')
            ->when($q, fn ($w) => $w->where(function ($x) use ($q) {
                $x->where('tanamen.nama_tanaman', 'like', "%{$q}%")
                    ->orWhere('panens.keterangan', 'like', "%{$q}%");
            }))
            ->when($dari, fn ($w) => $w->whereDate('panens.tgl_panen', '>=', $dari))
            ->when($sampai, fn ($w) => $w->whereDate('panens.tgl_panen', '<=', $sampai))
            ->groupBy('semais.id_semai', 'tanamen.nama_tanaman')
            ->orderByDesc('semais.id_semai');
    }

    public function create()
    {
        return view('semai.create', ['tanaman' => Tanaman::all(), 'siklus' => Siklus::with('tanaman')->get(), 'mejas' => Meja::all()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_tanaman' => 'required|exists:tanamen,id_tanaman',
            'id_siklus' => 'nullable|exists:sikluses,id_siklus',
            'id_meja' => 'nullable|exists:mejas,id_meja',
            'jumlah_benih' => 'required|integer|min:1',
            'tgl_awal_semai' => 'required|date',
            'tgl_akhir_semai' => 'required|date|after_or_equal:tgl_awal_semai',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($err = $this->cekSiklusDenganTanaman($data)) {
            return back()->withErrors(['id_siklus' => $err])->withInput();
        }

        Semai::create($data);

        return redirect()->route('semai.index')->with('success', 'Data semai berhasil ditambahkan.');
    }

    public function edit($id)
    {
        return view('semai.edit', ['semai' => Semai::findOrFail($id), 'tanaman' => Tanaman::all(), 'siklus' => Siklus::with('tanaman')->get(), 'mejas' => Meja::all()]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'id_tanaman' => 'required|exists:tanamen,id_tanaman',
            'id_siklus' => 'nullable|exists:sikluses,id_siklus',
            'id_meja' => 'nullable|exists:mejas,id_meja',
            'jumlah_benih' => 'required|integer|min:1',
            'tgl_awal_semai' => 'required|date',
            'tgl_akhir_semai' => 'required|date|after_or_equal:tgl_awal_semai',
            'status' => 'required|in:proses,selesai,gagal',
            'benih_berhasil' => 'required|integer|min:0',
            'benih_gagal' => 'required|integer|min:0',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($err = $this->cekSiklusDenganTanaman($data)) {
            return back()->withErrors(['id_siklus' => $err])->withInput();
        }

        // Logika: hasil (berhasil + gagal) tidak boleh melebihi jumlah benih yang disemai
        $totalHasil = $data['benih_berhasil'] + $data['benih_gagal'];
        $semaiLama = Semai::findOrFail($id);
        $totalLama = $semaiLama->benih_berhasil + $semaiLama->benih_gagal;

        // Data lama yang sudah melanggar batas tetap boleh disimpan asal tidak diperparah
        if ($totalHasil > $data['jumlah_benih'] && $totalHasil > $totalLama) {
            return back()->withErrors([
                'benih_gagal' => "Total benih berhasil + gagal ({$totalHasil}) melebihi jumlah benih disemai ({$data['jumlah_benih']}).",
            ])->withInput();
        }

        $semaiLama->update($data);

        return redirect()->route('semai.index')->with('success', 'Data semai berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Semai::findOrFail($id)->delete();

        return redirect()->route('semai.index')->with('success', 'Data semai berhasil dihapus.');
    }

    public function dataSelesai()
    {
        // "Riwayat Selesai" tidak lagi halaman terpisah — masuk ke Data Tahap
        return redirect()->route('semai.index', ['tahap' => 'semai', 'status' => 'selesai']);
    }

    public function show($id)
    {
        return redirect()->route('semai.index');
    }

    /**
     * Detail satu batch untuk ikon mata di Data Tahap.
     * Mengembalikan seluruh jejak siklus satu batch — Semai -> Peremajaan ->
     * Pendewasaan -> Panen — termasuk sub-baris hasil split multi-meja, sehingga
     * pengguna tidak perlu berpindah tab untuk mengecek kelengkapan datanya.
     *
     * Dipanggil lewat AJAX oleh tombol mata; sengaja berupa HTML fragmen
     * (bukan JSON) supaya tata letaknya tetap memakai Blade.
     */
    public function detail($id)
    {
        $semai = Semai::with([
            'tanaman:id_tanaman,nama_tanaman',
            'siklus:id_siklus,nama_siklus,waktu_siklus',
            'meja:id_meja,meja,jumlah_lubang',
        ])->with([
            'peremajaan' => fn ($q) => $q->with([
                'meja:id_meja,meja,jumlah_lubang',
                'pendewasaan' => fn ($q2) => $q2->with([
                    'meja:id_meja,meja,jumlah_lubang',
                    'panen',
                ]),
            ]),
        ])->findOrFail($id);

        $pers = $semai->peremajaan->sortBy('id_peremajaan')->values();
        $pens = $pers->flatMap->pendewasaan->sortBy('id_pendewasaan')->values();
        $panens = $pens->flatMap->panen->sortBy('id_panen')->values();

        return view('semai._detail', compact('semai', 'pers', 'pens', 'panens'));
    }

    /** Siklus harus milik tanaman yang sama — dropdown difilter per tanaman, ini penjaga sisi server */
    private function cekSiklusDenganTanaman(array $data): ?string
    {
        $idSiklus = $data['id_siklus'] ?? null;
        if (! $idSiklus) {
            return null;
        }
        $siklus = Siklus::find($idSiklus);
        if ($siklus && $siklus->id_tanaman != $data['id_tanaman']) {
            return 'Siklus "'.$siklus->nama_siklus.'" tidak sesuai dengan tanaman terpilih.';
        }

        return null;
    }
}
