<?php
namespace App\Http\Controllers;
use App\Models\{Siklus, Tanaman};
use Illuminate\Http\Request;

class SiklusController extends Controller
{
    public function index()  { return view('siklus.index', ['sikluses' => Siklus::with('tanaman')->get()]); }
    public function create() { return view('siklus.create', ['tanaman' => Tanaman::all()]); }
    public function store(Request $request) {
        $request->validate(['nama_siklus' => 'required', 'waktu_siklus' => 'required|integer|min:1']);
        Siklus::create($request->only('id_tanaman','nama_siklus','waktu_siklus'));
        return redirect()->route('siklus.index')->with('success', 'Siklus berhasil ditambahkan.');
    }
    public function edit($id) { return view('siklus.edit', ['siklus' => Siklus::findOrFail($id), 'tanaman' => Tanaman::all()]); }
    public function update(Request $request, $id) {
        $request->validate(['nama_siklus' => 'required', 'waktu_siklus' => 'required|integer|min:1']);
        Siklus::findOrFail($id)->update($request->only('id_tanaman','nama_siklus','waktu_siklus'));
        return redirect()->route('siklus.index')->with('success', 'Siklus berhasil diperbarui.');
    }
    public function destroy($id) { Siklus::findOrFail($id)->delete(); return redirect()->route('siklus.index')->with('success','Siklus dihapus.'); }
    public function show($id) { return redirect()->route('siklus.index'); }
}
