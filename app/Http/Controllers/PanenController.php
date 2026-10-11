<?php

namespace App\Http\Controllers;

use App\Models\Panen;
use App\Models\Pendewasaan;
use Illuminate\Http\Request;

class PanenController extends Controller
{
    public function index(Request $request)
    {
        // Halaman index tahap ini tidak ada di sidebar — digabung ke "Data Tahap (Detail)".
        // Route tetap ada agar link lama & test redirect tidak rusak.
        return redirect()->route('semai.index', ['tahap' => 'panen']);

    }

    public function create()
    {
        return view('panen.create', ['pendewasaans' => Pendewasaan::with(['meja', 'peremajaan.semai.tanaman'])->latest()->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        if ($err = $this->cekJumlahMelebihiHasilPendewasaan($data, null)) {
            return back()->withErrors(['panen_berhasil' => $err])->withInput();
        }
        Panen::create($data);

        return redirect()->route('panen.index')->with('success', 'Data panen berhasil dicatat.');
    }

    public function edit($id)
    {
        $panen = Panen::findOrFail($id);
        $pendewasaans = Pendewasaan::with(['meja', 'peremajaan.semai.tanaman'])->latest()->get();

        return view('panen.edit', ['panen' => $panen, 'pendewasaans' => $pendewasaans]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validasi($request);
        if ($err = $this->cekJumlahMelebihiHasilPendewasaan($data, (int) $id)) {
            return back()->withErrors(['panen_berhasil' => $err])->withInput();
        }
        Panen::findOrFail($id)->update($data);

        return redirect()->route('panen.index')->with('success', 'Data panen diperbarui.');
    }

    public function destroy($id)
    {
        Panen::findOrFail($id)->delete();

        return redirect()->route('panen.index')->with('success', 'Dihapus.');
    }

    public function show($id)
    {
        return redirect()->route('panen.index');
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'id_pendewasaan' => 'required|exists:pendewasaans,id_pendewasaan',
            'panen_berhasil' => 'required|integer|min:0',
            'panen_gagal' => 'required|integer|min:0',
            'tgl_panen' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
        ]);
    }

    /**
     * Logika rantai data: total panen (kumulatif per pendewasaan)
     * tidak boleh melebihi tanaman hasil dari pendewasaan induknya.
     */
    private function cekJumlahMelebihiHasilPendewasaan(array $data, ?int $kecualiId): ?string
    {
        $pendewasaan = Pendewasaan::findOrFail($data['id_pendewasaan']);
        if ($pendewasaan->tanaman_berhasil <= 0) {
            return null;
        }

        $totalBaru = (int) $data['panen_berhasil'] + (int) $data['panen_gagal'];
        $terpakai = (int) Panen::where('id_pendewasaan', $pendewasaan->id_pendewasaan)
            ->when($kecualiId !== null, fn ($q) => $q->where('id_panen', '!=', $kecualiId))
            ->selectRaw('COALESCE(SUM(panen_berhasil + panen_gagal), 0) as total')
            ->value('total');

        if ($terpakai + $totalBaru > $pendewasaan->tanaman_berhasil) {
            // Data lama yang sudah melanggar batas tetap boleh diedit asal tidak diperparah
            $lama = $kecualiId !== null ? Panen::find($kecualiId) : null;
            $totalLama = $lama ? ($lama->panen_berhasil + $lama->panen_gagal) : 0;

            if ($totalBaru > $totalLama) {
                return "Total panen ({$totalBaru} + {$terpakai} terpakai) melebihi hasil pendewasaan ({$pendewasaan->tanaman_berhasil} tanaman berhasil).";
            }
        }

        return null;
    }
}
