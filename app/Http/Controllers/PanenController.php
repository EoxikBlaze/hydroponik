<?php
namespace App\Http\Controllers;
use App\Models\{Panen, Pendewasaan};
use Illuminate\Http\Request;

class PanenController extends Controller
{
    public function index()  { return view('panen.index', ['panens' => Panen::with('pendewasaan')->latest()->get()]); }
    public function create() { return view('panen.create', ['pendewasaans' => Pendewasaan::with('meja')->get()]); }
    public function store(Request $request) {
        $request->validate(['id_pendewasaan' => 'required', 'panen_berhasil' => 'required|integer|min:0', 'panen_gagal' => 'required|integer|min:0', 'tgl_panen' => 'required|date']);
        Panen::create($request->only('id_pendewasaan','panen_berhasil','panen_gagal','tgl_panen','keterangan'));
        return redirect()->route('panen.index')->with('success', 'Data panen berhasil dicatat.');
    }
    public function edit($id) { return view('panen.edit', ['panen' => Panen::findOrFail($id), 'pendewasaans' => Pendewasaan::all()]); }
    public function update(Request $request, $id) {
        Panen::findOrFail($id)->update($request->only('id_pendewasaan','panen_berhasil','panen_gagal','tgl_panen','keterangan'));
        return redirect()->route('panen.index')->with('success', 'Data panen diperbarui.');
    }
    public function destroy($id) { Panen::findOrFail($id)->delete(); return redirect()->route('panen.index')->with('success','Dihapus.'); }
    public function show($id) { return redirect()->route('panen.index'); }
}
