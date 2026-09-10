<?php
namespace App\Http\Controllers;
use App\Models\{Peremajaan, Semai, Meja};
use Illuminate\Http\Request;

class PeremajaanController extends Controller
{
    public function index()  { return view('peremajaan.index', ['peremajaans' => Peremajaan::with(['semai.tanaman','meja'])->latest()->get()]); }
    public function create() { return view('peremajaan.create', ['semais' => Semai::with('tanaman')->where('status','selesai')->get(), 'mejas' => Meja::all()]); }
    public function store(Request $request) {
        $request->validate(['benih_berhasil' => 'required|integer|min:0', 'benih_gagal' => 'required|integer|min:0', 'tgl_awal_peremajaan' => 'required|date', 'tgl_akhir_peremajaan' => 'required|date']);
        Peremajaan::create($request->only('id_semai','id_meja','benih_berhasil','benih_gagal','tgl_awal_peremajaan','tgl_akhir_peremajaan','keterangan'));
        return redirect()->route('peremajaan.index')->with('success', 'Data peremajaan berhasil ditambahkan.');
    }
    public function edit($id) { return view('peremajaan.edit', ['peremajaan' => Peremajaan::findOrFail($id), 'semais' => Semai::with('tanaman')->get(), 'mejas' => Meja::all()]); }
    public function update(Request $request, $id) {
        Peremajaan::findOrFail($id)->update($request->only('id_semai','id_meja','benih_berhasil','benih_gagal','tgl_awal_peremajaan','tgl_akhir_peremajaan','keterangan'));
        return redirect()->route('peremajaan.index')->with('success', 'Data peremajaan berhasil diperbarui.');
    }
    public function destroy($id) { Peremajaan::findOrFail($id)->delete(); return redirect()->route('peremajaan.index')->with('success','Dihapus.'); }
    public function show($id) { return redirect()->route('peremajaan.index'); }
}
