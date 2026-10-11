<?php

namespace App\Http\Controllers;

use App\Models\Meja;
use App\Models\Panen;
use App\Models\Pendewasaan;
use App\Models\Peremajaan;
use App\Models\Semai;
use App\Models\Siklus;
use App\Models\Tanaman;
use App\Models\AlertLog;
use App\Services\NotifWaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Alur tanam satu pintu untuk petani:
 * Mulai Tanam -> Semai -> (Tandai Selesai) -> Peremajaan -> (Tandai Selesai) -> Pendewasaan -> (Catat Panen).
 * Tiap fase punya MEJA SENDIRI (dipilih saat perpindahan) dan jumlah lubang meja
 * menjadi batas kapasitasnya. Durasi tiap fase dari master Durasi Siklus.
 */
class TanamController extends Controller
{
    /** Durasi default (hari) bila master Durasi Siklus belum diisi */
    private const DURASI_DEFAULT = ['Semai' => 7, 'Peremajaan' => 14, 'Pendewasaan' => 21];

    private const TAHAP = ['semai', 'peremajaan', 'pendewasaan'];

    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q'));
        $bulan = (string) $request->get('bulan');   // format YYYY-MM

        $semais = Semai::with(['tanaman', 'meja', 'peremajaan.meja', 'peremajaan.pendewasaan.meja', 'peremajaan.pendewasaan.panen'])
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->whereHas('tanaman', fn ($t) => $t->where('nama_tanaman', 'like', "%{$q}%"))
                        ->orWhere('keterangan', 'like', "%{$q}%")
                        ->orWhere('id_semai', $q);
                });
            })
            ->when($bulan !== '' && preg_match('/^\d{4}-\d{2}$/', $bulan), function ($w) use ($bulan) {
                [$tahun, $bln] = explode('-', $bulan);
                $w->whereYear('tgl_awal_semai', (int) $tahun)
                    ->whereMonth('tgl_awal_semai', (int) $bln);
            })
            ->latest('id_semai')->get();

        $rantai = $semais->map(function (Semai $semai): array {
            // Satu tahap bisa punya BEBERAPA baris (hasil split ke banyak meja).
            // Karena itu tahap ditentukan dari KEberadaan anak di seluruh baris,
            // bukan dari baris PERTAMA — baris pertama bisa tertinggal tanpa
            // anak padahal baris lain sudah lanjut (bug: data sudah di
            // Pendewasaan tapi kartu masih membaca Peremajaan).
            $pers = $semai->peremajaan->sortBy('id_peremajaan')->values();
            $pens = $pers->flatMap(function ($p) {
                // Sambil ditempel, induknya diset sebagai relasi supaya view tidak
                // perlu query tambahan saat menghitung batas baris (fallback lama).
                return $p->pendewasaan->each(fn ($d) => $d->setRelation('peremajaan', $p));
            })->sortBy('id_pendewasaan')->values();
            $pans = $pens->flatMap(fn ($d) => $d->panen)->sortBy('id_panen')->values();

            $tahap = match (true) {
                $pans->isNotEmpty() => 4,
                $pens->isNotEmpty() => 3,
                $pers->isNotEmpty() => 2,
                default => 1,
            };

            // Wakil tahap untuk tampilan: baris yang SUDAH punya anak (paling
            // maju), supaya tanggal & meja yang tampil bukan baris tertinggal.
            $per = $pers->first(fn ($p) => $p->pendewasaan->isNotEmpty()) ?? $pers->first();
            $pen = $pens->first(fn ($d) => $d->panen->isNotEmpty()) ?? $pens->first();
            $pan = $pans->first();

            // SATU TOMBOL PER BARIS YANG BELUM LANJUT — bukan satu tombol untuk
            // seluruh tahap. Tanpa ini, setelah split (satu batch jadi beberapa
            // baris meja) hanya baris wakil yang punya tombol; baris sisanya
            // tersangkung selamanya karena tidak pernah dikirim ke server.
            // Batas tiap tombol = batas baris itu (jumlah_masuk), sehingga
            // angka yang ditampilkan form persis yang diterima server.
            $tombol = [];

            if ($tahap === 1) {
                $tombol[] = [
                    'tipe' => 'semai', 'id' => $semai->id_semai,
                    'judul' => 'Tandai Semai Selesai',
                    'batas' => (int) $semai->jumlah_benih,
                    'meja' => $semai->meja?->meja,
                ];
            }

            foreach ($pers as $p) {
                if ($p->pendewasaan->isEmpty()) {
                    $tombol[] = [
                        'tipe' => 'peremajaan', 'id' => $p->id_peremajaan,
                        'judul' => 'Tandai Peremajaan Selesai',
                        'batas' => (int) ($p->jumlah_masuk ?? $semai->benih_berhasil),
                        'meja' => $p->meja?->meja,
                    ];
                }
            }

            foreach ($pens as $pn) {
                if ($pn->panen->isEmpty()) {
                    $tombol[] = [
                        'tipe' => 'pendewasaan', 'id' => $pn->id_pendewasaan,
                        'judul' => 'Catat Hasil Panen',
                        'batas' => (int) ($pn->jumlah_masuk ?? $pn->peremajaan?->benih_berhasil),
                        'meja' => $pn->meja?->meja,
                    ];
                }
            }

            return [
                'semai' => $semai,
                'per' => $per,
                'pen' => $pen,
                'pan' => $pan,
                'tahap' => $tahap,
                'jmlPer' => $pers->count(),
                'jmlPend' => $pens->count(),
                'tombol' => $tombol,
            ];
        });

        // Daftar bulan untuk dropdown — diambil dari seluruh data tanpa
        // filter bulan supaya pilihannya tidak menyusut saat difilter.
        $daftarBulan = Semai::pluck('tgl_awal_semai')
            ->filter()
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()->sort()->reverse()->values();

        // Kelompokkan hasil per bulan agar tampilannya "per bulan".
        $perBulan = $rantai->groupBy(
            fn ($r) => $r['semai']->tgl_awal_semai
                ? Carbon::parse($r['semai']->tgl_awal_semai)->format('Y-m')
                : 'lainnya'
        );

        return view('tanam.index', [
            'rantai' => $rantai,
            'perBulan' => $perBulan,
            'daftarBulan' => $daftarBulan,
            'qCari' => $q,
            'bulanAktif' => $bulan,
            'tanamans' => Tanaman::all(),
            'mejas' => Meja::all(),
        ]);
    }

    public function mulai(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_tanaman' => 'required|exists:tanamen,id_tanaman',
            'id_meja' => 'required|exists:mejas,id_meja',
            'jumlah_benih' => 'required|integer|min:1',
            'tgl_mulai' => 'required|date',
        ]);

        // Batas kapasitas = jumlah lubang meja semai
        $meja = Meja::findOrFail($data['id_meja']);
        if ($data['jumlah_benih'] > $meja->jumlah_lubang) {
            return back()->withErrors([
                'jumlah_benih' => "Benih ({$data['jumlah_benih']}) melebihi jumlah lubang {$meja->meja} ({$meja->jumlah_lubang} lubang).",
            ])->withInput();
        }

        $siklus = $this->siklus((int) $data['id_tanaman'], 'Semai');
        $durasi = (int) ($siklus?->waktu_siklus ?? self::DURASI_DEFAULT['Semai']);

        Semai::create([
            'id_tanaman' => $data['id_tanaman'],
            'id_siklus' => $siklus?->id_siklus,
            'id_meja' => $data['id_meja'],
            'jumlah_benih' => $data['jumlah_benih'],
            'tgl_awal_semai' => $data['tgl_mulai'],
            'tgl_akhir_semai' => Carbon::parse($data['tgl_mulai'])->addDays($durasi)->toDateString(),
            'status' => 'proses',
        ]);

        return redirect()->route('tanam.index')->with(
            'success',
            "Tanam dimulai di {$meja->meja}! Tahap Semai berjalan {$durasi} hari (durasi dari master data)."
        );
    }

    /** Hapus satu batch lengkap beserta seluruh tahapannya — khusus admin. */
    public function hapus(int $id): RedirectResponse
    {
        abort_if(request()->user()?->level !== 'admin', 403);

        $semai = Semai::with('peremajaan.pendewasaan')->findOrFail($id);

        foreach ($semai->peremajaan as $per) {
            foreach ($per->pendewasaan as $pen) {
                Panen::where('id_pendewasaan', $pen->id_pendewasaan)->delete();
            }
            Pendewasaan::where('id_peremajaan', $per->id_peremajaan)->delete();
        }
        Peremajaan::where('id_semai', $semai->id_semai)->delete();
        $semai->delete();

        return redirect()->route('tanam.index')->with('success', "Batch #{$id} beserta seluruh tahapannya dihapus.");
    }

    public function selesai(Request $request, string $tipe, int $id): RedirectResponse
    {
        [$record, $batas, $induk] = $this->konteks($tipe, $id);

        // Anti double-submit: tahap yang sudah punya LANJUTAN tidak boleh
        // diproses ulang. Yang jadi patokan adalah KEberadaan anak, BUKAN
        // kolom status — status bisa diubah lewat form Edit tanpa membuat anak,
        // sehingga status='selesai' tanpa anak membuat kartu terkunci
        // (tombol muncul tapi selalu ditolak).
        $sudahLanjut = match ($tipe) {
            'semai' => $record->peremajaan()->exists(),
            'peremajaan' => $record->pendewasaan()->exists(),
            default => $record->panen()->exists(),
        };
        if ($sudahLanjut) {
            return redirect()->route('tanam.index')->with(
                'error',
                'Tahap ini sudah selesai sebelumnya — data tidak digandakan.'
            );
        }

        if ($tipe === 'pendewasaan') {
            return $this->selesaikanPendewasaan($request, $record, $batas);
        }

        $data = $request->validate([
            // Format lama: satu dropdown id_meja (seluruh hasil ke satu meja).
            'id_meja' => 'required_without:tujuan|nullable|integer|exists:mejas,id_meja',
            // Format split: beberapa meja tujuan + jatah masing-masing.
            'tujuan' => 'required_without:id_meja|array|min:1',
            'tujuan.*.id_meja' => 'required|integer|exists:mejas,id_meja',
            'tujuan.*.jumlah' => 'required|integer|min:1',
            'berhasil' => 'required|integer|min:0',
            'gagal' => 'required|integer|min:0',
            'tgl_selesai' => 'required|date',
        ]);

        $total = $data['berhasil'] + $data['gagal'];
        if ($total > $batas) {
            return back()->withErrors([
                'gagal' => "Total berhasil + gagal ({$total}) melebihi batas {$batas}.",
            ])->withInput();
        }

        // Normalisasi tujuan -> [id_meja => jumlah]. Format lama tetap didukung:
        // seluruh hasil berhasil pindah ke satu meja itu (tanpa field jumlah).
        $alokasi = [];
        if (! empty($data['tujuan'])) {
            foreach ($data['tujuan'] as $t) {
                $idMeja = (int) $t['id_meja'];
                $alokasi[$idMeja] = ($alokasi[$idMeja] ?? 0) + (int) $t['jumlah'];
            }
        } else {
            $alokasi[(int) $data['id_meja']] = (int) $data['berhasil'];
        }

        // Total alokasi harus persis sama dengan hasil berhasil — supaya tidak
        // ada tanaman yang "hilang" dari catatan atau tercatat dua kali.
        $totalAlokasi = array_sum($alokasi);
        if ($totalAlokasi !== (int) $data['berhasil']) {
            return back()->withErrors([
                'tujuan' => "Total alokasi meja ({$totalAlokasi}) harus sama dengan hasil berhasil ({$data['berhasil']}).",
            ])->withInput();
        }

        // Tiap meja tujuan harus muat dengan JATAHNYA SENDIRI (bukan gabungan),
        // sehingga 400 bisa dibelah ke dua meja berlubang 200.
        $mejaDipilih = [];
        foreach ($alokasi as $idMeja => $jml) {
            $m = Meja::find($idMeja);
            if (! $m) {
                return back()->withErrors(['id_meja' => 'Meja tujuan tidak ditemukan.'])->withInput();
            }
            if ($jml > $m->jumlah_lubang) {
                return back()->withErrors([
                    'id_meja' => "Benih pindah ({$jml}) melebihi jumlah lubang {$m->meja} ({$m->jumlah_lubang} lubang). Tambah tujuan lain atau kurangi jatahnya.",
                ])->withInput();
            }
            $mejaDipilih[$idMeja] = $m;
        }

        $namaMeja = implode(', ', array_map(fn (Meja $m) => $m->meja, $mejaDipilih));
        $tgl = $data['tgl_selesai'];

        // Atomik: kunci baris induk lalu cek ulang guard di dalam transaksi,
        // supaya double-click serentak tidak bisa sama-sama lolos lalu menggandakan.
        $hasil = DB::transaction(function () use ($tipe, $record, $induk, $data, $tgl, $alokasi, $namaMeja) {
            $kunci = match ($tipe) {
                'semai' => Semai::lockForUpdate()->findOrFail($record->id_semai),
                'peremajaan' => Peremajaan::lockForUpdate()->findOrFail($record->id_peremajaan),
            };

            // Cek ulang guard SETELAH kunci — request kedua akan menunggu lock
            // lalu melihat lanjutan sudah ada & dibatalkan. Patokan = anak,
            // bukan status (lihat catatan di guard luar transaksi).
            $sudah = match ($tipe) {
                'semai' => $kunci->peremajaan()->exists(),
                'peremajaan' => $kunci->pendewasaan()->exists(),
            };
            if ($sudah) {
                return 'Tahap ini sudah selesai sebelumnya — data tidak digandakan.';
            }

            if ($tipe === 'semai') {
                $kunci->update([
                    'benih_berhasil' => $data['berhasil'],
                    'benih_gagal' => $data['gagal'],
                    'tgl_akhir_semai' => $tgl,
                    'status' => 'selesai',
                ]);
                $durasi = $this->durasi($kunci->id_tanaman, 'Peremajaan');
                $tglAkhir = Carbon::parse($tgl)->addDays($durasi)->toDateString();

                // SATU BARIS PER MEJA TUJUAN — hasil dibelah (split), bukan digabung.
                foreach ($alokasi as $idMeja => $jml) {
                    Peremajaan::create([
                        'id_semai' => $kunci->id_semai,
                        'id_meja' => $idMeja,
                        'jumlah_masuk' => $jml,
                        'benih_berhasil' => 0,
                        'benih_gagal' => 0,
                        'tgl_awal_peremajaan' => $tgl,
                        'tgl_akhir_peremajaan' => $tglAkhir,
                        'keterangan' => 'Dibuat otomatis dari Tanam Saya',
                    ]);
                }

                return "Semai selesai ✅ Pindah ke {$namaMeja}. Tahap Peremajaan dibuat otomatis ({$durasi} hari, sampai {$tglAkhir}).";
            }

            $kunci->update([
                'benih_berhasil' => $data['berhasil'],
                'benih_gagal' => $data['gagal'],
                'tgl_akhir_peremajaan' => $tgl,
            ]);
            $durasi = $this->durasi($induk, 'Pendewasaan');
            $tglAkhir = Carbon::parse($tgl)->addDays($durasi)->toDateString();

            foreach ($alokasi as $idMeja => $jml) {
                Pendewasaan::create([
                    'id_peremajaan' => $kunci->id_peremajaan,
                    'id_meja' => $idMeja,
                    'jumlah_masuk' => $jml,
                    'tanaman_berhasil' => 0,
                    'tanaman_gagal' => 0,
                    'tgl_awal_pendewasaan' => $tgl,
                    'tgl_akhir_pendewasaan' => $tglAkhir,
                    'keterangan' => 'Dibuat otomatis dari Tanam Saya',
                ]);
            }

            return "Peremajaan selesai ✅ Pindah ke {$namaMeja}. Tahap Pendewasaan dibuat otomatis ({$durasi} hari, sampai {$tglAkhir}).";
        });

        if (str_contains($hasil, 'sudah selesai sebelumnya')) {
            return redirect()->route('tanam.index')->with('error', $hasil);
        }

        // Tahap berganti -> kabari WhatsApp: "pindah tanam"
        if ($tipe !== 'pendewasaan') {
            $label = $tipe === 'semai' ? 'Semai' : 'Peremajaan';
            app(NotifWaService::class)->catatDanKirim(
                'pindah_tanam',
                "{$label} → {$namaMeja} ({$data['berhasil']} pindah)"
            );
        }

        return redirect()->route('tanam.index')->with('success', $hasil);
    }

    /** Tahap terakhir: catat hasil tanaman sekaligus hasil panen, otomatis menjadi data Panen. */
    private function selesaikanPendewasaan(Request $request, Pendewasaan $record, int $batas): RedirectResponse
    {
        $data = $request->validate([
            'berhasil' => 'required|integer|min:0',
            'gagal' => 'required|integer|min:0',
            'panen_berhasil' => 'required|integer|min:0',
            'panen_gagal' => 'required|integer|min:0',
            'tgl_selesai' => 'required|date',
        ]);

        $total = $data['berhasil'] + $data['gagal'];
        if ($total > $batas) {
            return back()->withErrors(['gagal' => "Total berhasil + gagal ({$total}) melebihi batas {$batas}."])->withInput();
        }
        $totalPanen = $data['panen_berhasil'] + $data['panen_gagal'];
        if ($totalPanen > $data['berhasil']) {
            return back()->withErrors(['panen_gagal' => "Total panen ({$totalPanen}) melebihi tanaman berhasil ({$data['berhasil']})."])->withInput();
        }

        $record->update([
            'tanaman_berhasil' => $data['berhasil'],
            'tanaman_gagal' => $data['gagal'],
            'tgl_akhir_pendewasaan' => $data['tgl_selesai'],
        ]);

        Panen::create([
            'id_pendewasaan' => $record->id_pendewasaan,
            'panen_berhasil' => $data['panen_berhasil'],
            'panen_gagal' => $data['panen_gagal'],
            'tgl_panen' => $data['tgl_selesai'],
            'keterangan' => 'Dibuat otomatis dari Tanam Saya',
        ]);

        return redirect()->route('tanam.index')->with(
            'success',
            "Pendewasaan selesai 🌾 Panen tercatat: {$data['panen_berhasil']} berhasil, {$data['panen_gagal']} gagal. Siklus tanam TUNTAS!"
        );
    }

    /** @return array{0: object, 1: int, 2: int} [record, batas maksimum, id tanaman] */
    private function konteks(string $tipe, int $id): array
    {
        if (! in_array($tipe, self::TAHAP, true)) {
            abort(404);
        }

        $record = match ($tipe) {
            'semai' => Semai::findOrFail($id),
            'peremajaan' => Peremajaan::with('semai.tanaman')->findOrFail($id),
            default => Pendewasaan::with('peremajaan.semai')->findOrFail($id),
        };

        [$batas, $tanamanId] = match ($tipe) {
            'semai' => [(int) $record->jumlah_benih, (int) $record->id_tanaman],
            // jumlah_masuk = jatah baris ini saat pindah tahap (hasil split).
            // NULL = baris lama / tanpa split -> fallback ke batas induk (perilaku lama).
            'peremajaan' => [
                (int) ($record->jumlah_masuk ?? $record->semai?->benih_berhasil),
                (int) $record->semai?->id_tanaman,
            ],
            default => [
                (int) ($record->jumlah_masuk ?? $record->peremajaan?->benih_berhasil),
                (int) $record->peremajaan?->semai?->id_tanaman,
            ],
        };

        return [$record, $batas, $tanamanId];
    }

    /** Cari durasi tahap dari master Durasi Siklus milik tanaman. */
    private function siklus(int $idTanaman, string $nama): ?Siklus
    {
        return Siklus::where('id_tanaman', $idTanaman)
            ->whereRaw('LOWER(nama_siklus) = ?', [strtolower($nama)])
            ->first();
    }

    private function durasi(int $idTanaman, string $nama): int
    {
        return (int) ($this->siklus($idTanaman, $nama)?->waktu_siklus ?? self::DURASI_DEFAULT[$nama]);
    }
}
