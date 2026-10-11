<?php

namespace App\Http\Controllers;

use App\Models\Siklus;
use App\Models\Tanaman;
use Illuminate\Http\Request;

class SiklusController extends Controller
{
    public function index()
    {
        return view('siklus.index', ['sikluses' => Siklus::with('tanaman')->get()]);
    }

    public function create()
    {
        return view('siklus.create', ['tanaman' => Tanaman::all()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_tanaman' => 'nullable|exists:tanamen,id_tanaman',
            'nama_siklus' => 'required|max:100',
            'waktu_siklus' => 'required|integer|min:1',
        ]);
        Siklus::create($data);

        return redirect()->route('siklus.index')->with('success', 'Siklus berhasil ditambahkan.');
    }

    public function edit($id)
    {
        return view('siklus.edit', ['siklus' => Siklus::findOrFail($id), 'tanaman' => Tanaman::all()]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'id_tanaman' => 'nullable|exists:tanamen,id_tanaman',
            'nama_siklus' => 'required|max:100',
            'waktu_siklus' => 'required|integer|min:1',
        ]);
        Siklus::findOrFail($id)->update($data);

        return redirect()->route('siklus.index')->with('success', 'Siklus berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Siklus::findOrFail($id)->delete();

        return redirect()->route('siklus.index')->with('success', 'Siklus dihapus.');
    }

    public function show($id)
    {
        return redirect()->route('siklus.index');
    }

    /**
     * Tarik data master siklus sebagai CSV (Excel/LibreOffice).
     * Kolom disesuaikan kolom tabel sikluses + nama komoditas yang sudah join.
     */
    public function csv()
    {
        $baris = Siklus::with('tanaman')->orderBy('id_siklus')->get();

        return response()->streamDownload(function () use ($baris) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8
            fputcsv($out, ['ID', 'KOMODITAS', 'NAMA SIKLUS', 'DURASI (HARI)', 'DIBUAT']);
            foreach ($baris as $s) {
                fputcsv($out, [
                    $s->id_siklus,
                    $s->tanaman?->nama_tanaman ?? '-',
                    $s->nama_siklus,
                    $s->waktu_siklus,
                    optional($s->created_at)->format('d/m/Y H:i'),
                ]);
            }
            fclose($out);
        }, 'master-siklus-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
