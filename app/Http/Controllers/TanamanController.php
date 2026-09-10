<?php
namespace App\Http\Controllers;
use App\Models\{Tanaman, Meja};
use Illuminate\Http\Request;

class TanamanController extends Controller
{
    public function index()  { return view('tanaman.index', ['tanaman' => Tanaman::with('meja')->get()]); }
    public function create() { return view('tanaman.create', ['mejas' => Meja::all()]); }

    public function store(Request $request)
    {
        $request->validate(['nama_tanaman' => 'required|max:50']);
        Tanaman::create($request->only('nama_tanaman', 'id_meja'));
        return redirect()->route('tanaman.index')->with('success', 'Tanaman berhasil ditambahkan.');
    }

    public function edit($id) { return view('tanaman.edit', ['tanaman' => Tanaman::findOrFail($id), 'mejas' => Meja::all()]); }

    public function update(Request $request, $id)
    {
        $request->validate(['nama_tanaman' => 'required|max:50']);
        Tanaman::findOrFail($id)->update($request->only('nama_tanaman', 'id_meja'));
        return redirect()->route('tanaman.index')->with('success', 'Tanaman berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Tanaman::findOrFail($id)->delete();
        return redirect()->route('tanaman.index')->with('success', 'Tanaman berhasil dihapus.');
    }

    public function show($id) { return redirect()->route('tanaman.index'); }
}
