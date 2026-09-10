<?php
namespace App\Http\Controllers;
use App\Models\{Pendewasaan, Peremajaan, Meja};
use Illuminate\Http\Request;

class PendewasaanController extends Controller
{
    public function index()  { return view('pendewasaan.index', ['pendewasaans' => Pendewasaan::with(['peremajaan','meja'])->latest()->get()]); }
    public function create() { return view('pendewasaan.create', ['peremajaans' => Peremajaan::all(), 'mejas' => Meja::all()]); }
    public function store(Request $request) {
        $request->validate(['tanaman_berhasil' => 'required|integer|min:0', 'tanaman_gagal' => 'required|integer|min:0', 'tgl_awal_pendewasaan' => 'required|date', 'tgl_akhir_pendewasaan' => 'required|date']);
        Pendewasaan::create($request->only('id_peremajaan','id_meja','tanaman_berhasil','tanaman_gagal','tgl_awal_pendewasaan','tgl_akhir_pendewasaan','keterangan'));
        return redirect()->route('pendewasaan.index')->with('success', 'Data pendewasaan ditambahkan.');
    }
    public function edit($id) { return view('pendewasaan.edit', ['pendewasaan' => Pendewasaan::findOrFail($id), 'peremajaans' => Peremajaan::all(), 'mejas' => Meja::all()]); }
    public function update(Request $request, $id) {
        Pendewasaan::findOrFail($id)->update($request->only('id_peremajaan','id_meja','tanaman_berhasil','tanaman_gagal','tgl_awal_pendewasaan','tgl_akhir_pendewasaan','keterangan'));
        return redirect()->route('pendewasaan.index')->with('success', 'Data pendewasaan diperbarui.');
    }
    public function destroy($id) { Pendewasaan::findOrFail($id)->delete(); return redirect()->route('pendewasaan.index')->with('success','Dihapus.'); }
    public function show($id) { return redirect()->route('pendewasaan.index'); }
}
