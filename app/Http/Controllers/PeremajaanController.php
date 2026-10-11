<?php

namespace App\Http\Controllers;

use App\Models\Meja;
use App\Models\Peremajaan;
use App\Models\Semai;
use App\Models\Siklus;
use Illuminate\Http\Request;

class PeremajaanController extends Controller
{
    public function index(Request $request)
    {
        // Halaman index tahap ini tidak ada di sidebar — digabung ke "Data Tahap (Detail)".
        // Route tetap ada agar link lama & test redirect tidak rusak.
        return redirect()->route('semai.index', ['tahap' => 'peremajaan']);

    }

    public function create()
    {
        return view('peremajaan.create', ['semais' => Semai::with('tanaman')->where('status', 'selesai')->get(), 'mejas' => Meja::all(), 'siklus' => Siklus::all()]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        if ($err = $this->cekKelayakanSemai($data)) {
            return back()->withErrors($err)->withInput();
        }
        if ($err = $this->cekJumlahMelebihiHasilSemai($data, null)) {
            return back()->withErrors(['benih_berhasil' => $err])->withInput();
        }
        Peremajaan::create($data);

        // Pindah tanam bibit semai -> meja peremajaan: kabari WhatsApp
        $meja = \App\Models\Meja::find($data['id_meja'] ?? null)?->meja ?? '-';
        app(\App\Services\NotifWaService::class)->catatDanKirim(
            'pindah_tanam',
            "Semai → {$meja} ({$data['benih_berhasil']} bibit)"
        );

        return redirect()->route('peremajaan.index')->with('success', 'Data peremajaan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $peremajaan = Peremajaan::findOrFail($id);
        // daftar semai selesai + pertahankan semai yang sedang terhubung
        $semais = Semai::with('tanaman')
            ->where('status', 'selesai')
            ->orWhere('id_semai', $peremajaan->id_semai)
            ->get();

        return view('peremajaan.edit', ['peremajaan' => $peremajaan, 'semais' => $semais, 'mejas' => Meja::all(), 'siklus' => Siklus::all()]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validasi($request);
        if ($err = $this->cekKelayakanSemai($data)) {
            return back()->withErrors($err)->withInput();
        }
        if ($err = $this->cekJumlahMelebihiHasilSemai($data, (int) $id)) {
            return back()->withErrors(['benih_berhasil' => $err])->withInput();
        }
        Peremajaan::findOrFail($id)->update($data);

        return redirect()->route('peremajaan.index')->with('success', 'Data peremajaan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Peremajaan::findOrFail($id)->delete();

        return redirect()->route('peremajaan.index')->with('success', 'Dihapus.');
    }

    public function show($id)
    {
        return redirect()->route('peremajaan.index');
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'id_semai' => 'required|exists:semais,id_semai',
            'id_meja' => 'nullable|exists:mejas,id_meja',
            'benih_berhasil' => 'required|integer|min:0',
            'benih_gagal' => 'required|integer|min:0',
            'tgl_awal_peremajaan' => 'required|date',
            'tgl_akhir_peremajaan' => 'required|date|after_or_equal:tgl_awal_peremajaan',
            'keterangan' => 'nullable|string|max:255',
        ]);
    }

    /** Peremajaan hanya boleh dari semai berstatus selesai (sama dengan isi dropdown) */
    private function cekKelayakanSemai(array $data): ?array
    {
        $semai = Semai::findOrFail($data['id_semai']);
        if ($semai->status !== 'selesai') {
            return ['id_semai' => 'Peremajaan hanya bisa dari data semai yang berstatus selesai.'];
        }

        return null;
    }

    /**
     * Logika rantai data: total benih peremajaan (kumulatif per semai)
     * tidak boleh melebihi benih hasil dari semai induknya.
     */
    private function cekJumlahMelebihiHasilSemai(array $data, ?int $kecualiId): ?string
    {
        $semai = Semai::findOrFail($data['id_semai']);
        if ($semai->benih_berhasil <= 0) {
            return null;   // hasil semai belum diisi, jangan batasi
        }

        $totalBaru = (int) $data['benih_berhasil'] + (int) $data['benih_gagal'];
        $terpakai = (int) Peremajaan::where('id_semai', $semai->id_semai)
            ->when($kecualiId !== null, fn ($q) => $q->where('id_peremajaan', '!=', $kecualiId))
            ->selectRaw('COALESCE(SUM(benih_berhasil + benih_gagal), 0) as total')
            ->value('total');

        if ($terpakai + $totalBaru > $semai->benih_berhasil) {
            // Data lama yang sudah melanggar batas tetap boleh diedit asal tidak diperparah
            $lama = $kecualiId !== null ? Peremajaan::find($kecualiId) : null;
            $totalLama = $lama ? ($lama->benih_berhasil + $lama->benih_gagal) : 0;

            if ($totalBaru > $totalLama) {
                return "Total benih peremajaan ({$totalBaru} + {$terpakai} terpakai) melebihi hasil semai ({$semai->benih_berhasil} benih berhasil).";
            }
        }

        return null;
    }
}
