<?php
namespace App\Http\Controllers;
use App\Models\Meja;
use Illuminate\Http\Request;

class MejaController extends Controller
{
    public function index()   { return view('meja.index', ['mejas' => Meja::latest()->get()]); }
    public function create()  { return view('meja.create'); }

    public function store(Request $request)
    {
        $request->validate(['meja' => 'required|max:50', 'jumlah_lubang' => 'required|integer|min:1']);
        Meja::create($request->only('meja', 'jumlah_lubang'));
        return redirect()->route('meja.index')->with('success', 'Meja berhasil ditambahkan.');
    }

    public function edit($id) { return view('meja.edit', ['meja' => Meja::findOrFail($id)]); }

    public function update(Request $request, $id)
    {
        $request->validate(['meja' => 'required|max:50', 'jumlah_lubang' => 'required|integer|min:1']);
        Meja::findOrFail($id)->update($request->only('meja', 'jumlah_lubang'));
        return redirect()->route('meja.index')->with('success', 'Meja berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Meja::findOrFail($id)->delete();
        return redirect()->route('meja.index')->with('success', 'Meja berhasil dihapus.');
    }

    public function show($id) { return redirect()->route('meja.index'); }
}
