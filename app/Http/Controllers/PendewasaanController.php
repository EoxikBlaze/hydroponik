<?php

namespace App\Http\Controllers;

use App\Models\Meja;
use App\Models\Pendewasaan;
use App\Models\Peremajaan;
use App\Models\Siklus;
use Illuminate\Http\Request;

class PendewasaanController extends Controller
{
    public function index(Request $request)
    {
        // Halaman index tahap ini tidak ada di sidebar — digabung ke "Data Tahap (Detail)".
        // Route tetap ada agar link lama & test redirect tidak rusak.
        return redirect()->route('semai.index', ['tahap' => 'pendewasaan']);

    }

    public function create()
    {
        return view('pendewasaan.create', ['peremajaans' => Peremajaan::with(['semai.tanaman', 'meja'])->latest()->get(), 'mejas' => Meja::all(), 'siklus' => Siklus::all()]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        if ($err = $this->cekJumlahMelebihiHasilPeremajaan($data, null)) {
            return back()->withErrors(['tanaman_berhasil' => $err])->withInput();
        }
        Pendewasaan::create($data);

        // Pindah tanaman peremajaan -> meja pendewasaan: kabari WhatsApp
        $meja = \App\Models\Meja::find($data['id_meja'] ?? null)?->meja ?? '-';
        app(\App\Services\NotifWaService::class)->catatDanKirim(
            'pindah_tanam',
            "Peremajaan → {$meja} ({$data['tanaman_berhasil']} tanaman)"
        );

        return redirect()->route('pendewasaan.index')->with('success', 'Data pendewasaan ditambahkan.');
    }

    public function edit($id)
    {
        $pendewasaan = Pendewasaan::findOrFail($id);

        return view('pendewasaan.edit', [
            'pendewasaan' => $pendewasaan,
            'peremajaans' => Peremajaan::with(['semai.tanaman', 'meja'])->latest()->get(),
            'mejas' => Meja::all(),
            'siklus' => Siklus::all(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validasi($request);
        if ($err = $this->cekJumlahMelebihiHasilPeremajaan($data, (int) $id)) {
            return back()->withErrors(['tanaman_berhasil' => $err])->withInput();
        }
        Pendewasaan::findOrFail($id)->update($data);

        return redirect()->route('pendewasaan.index')->with('success', 'Data pendewasaan diperbarui.');
    }

    public function destroy($id)
    {
        Pendewasaan::findOrFail($id)->delete();

        return redirect()->route('pendewasaan.index')->with('success', 'Dihapus.');
    }

    public function show($id)
    {
        return redirect()->route('pendewasaan.index');
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'id_peremajaan' => 'required|exists:peremajaans,id_peremajaan',
            'id_meja' => 'nullable|exists:mejas,id_meja',
            'tanaman_berhasil' => 'required|integer|min:0',
            'tanaman_gagal' => 'required|integer|min:0',
            'tgl_awal_pendewasaan' => 'required|date',
            'tgl_akhir_pendewasaan' => 'required|date|after_or_equal:tgl_awal_pendewasaan',
            'keterangan' => 'nullable|string|max:255',
        ]);
    }

    /**
     * Logika rantai data: total tanaman pendewasaan (kumulatif per peremajaan)
     * tidak boleh melebihi benih hasil dari peremajaan induknya.
     */
    private function cekJumlahMelebihiHasilPeremajaan(array $data, ?int $kecualiId): ?string
    {
        $peremajaan = Peremajaan::findOrFail($data['id_peremajaan']);
        if ($peremajaan->benih_berhasil <= 0) {
            return null;
        }

        $totalBaru = (int) $data['tanaman_berhasil'] + (int) $data['tanaman_gagal'];
        $terpakai = (int) Pendewasaan::where('id_peremajaan', $peremajaan->id_peremajaan)
            ->when($kecualiId !== null, fn ($q) => $q->where('id_pendewasaan', '!=', $kecualiId))
            ->selectRaw('COALESCE(SUM(tanaman_berhasil + tanaman_gagal), 0) as total')
            ->value('total');

        if ($terpakai + $totalBaru > $peremajaan->benih_berhasil) {
            // Data lama yang sudah melanggar batas tetap boleh diedit asal tidak diperparah
            $lama = $kecualiId !== null ? Pendewasaan::find($kecualiId) : null;
            $totalLama = $lama ? ($lama->tanaman_berhasil + $lama->tanaman_gagal) : 0;

            if ($totalBaru > $totalLama) {
                return "Total tanaman pendewasaan ({$totalBaru} + {$terpakai} terpakai) melebihi hasil peremajaan ({$peremajaan->benih_berhasil} benih berhasil).";
            }
        }

        return null;
    }
}
