<?php
namespace App\Http\Controllers;
use App\Models\{Semai, Tanaman, Siklus};
use Illuminate\Http\Request;

class SemaiController extends Controller
{
    public function index()  { return view('semai.index', ['semais' => Semai::with(['tanaman','siklus'])->latest()->get()]); }
    public function create() { return view('semai.create', ['tanaman' => Tanaman::all(), 'siklus' => Siklus::all()]); }

    public function store(Request $request)
    {
        $request->validate(['id_tanaman' => 'required', 'jumlah_benih' => 'required|integer|min:1', 'tgl_awal_semai' => 'required|date', 'tgl_akhir_semai' => 'required|date|after_or_equal:tgl_awal_semai']);
        Semai::create($request->only('id_tanaman','id_siklus','jumlah_benih','tgl_awal_semai','tgl_akhir_semai','keterangan'));
        return redirect()->route('semai.index')->with('success', 'Data semai berhasil ditambahkan.');
    }

    public function edit($id) { return view('semai.edit', ['semai' => Semai::findOrFail($id), 'tanaman' => Tanaman::all(), 'siklus' => Siklus::all()]); }

    public function update(Request $request, $id)
    {
        $request->validate(['id_tanaman' => 'required', 'jumlah_benih' => 'required|integer|min:1', 'tgl_awal_semai' => 'required|date', 'tgl_akhir_semai' => 'required|date']);
        Semai::findOrFail($id)->update($request->only('id_tanaman','id_siklus','jumlah_benih','tgl_awal_semai','tgl_akhir_semai','keterangan','status','benih_berhasil','benih_gagal'));
        return redirect()->route('semai.index')->with('success', 'Data semai berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Semai::findOrFail($id)->delete();
        return redirect()->route('semai.index')->with('success', 'Data semai berhasil dihapus.');
    }

    public function dataSelesai() { return view('semai.index', ['semais' => Semai::with(['tanaman','siklus'])->where('status','selesai')->get()]); }
    public function show($id) { return redirect()->route('semai.index'); }
    public function getTanamanBySiklus(Request $request) { return response()->json(Tanaman::where('id_tanaman', $request->id_tanaman)->first()); }
}
