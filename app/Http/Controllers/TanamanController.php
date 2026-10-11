<?php

namespace App\Http\Controllers;

use App\Models\Meja;
use App\Models\Tanaman;
use Illuminate\Http\Request;

class TanamanController extends Controller
{
    public function index()
    {
        return view('tanaman.index', ['tanaman' => Tanaman::with('meja')->get()]);
    }

    public function create()
    {
        return view('tanaman.create', ['mejas' => Meja::all()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_tanaman' => 'required|max:50',
            'id_meja' => 'nullable|exists:mejas,id_meja',
        ]);
        Tanaman::create($data);

        return redirect()->route('tanaman.index')->with('success', 'Tanaman berhasil ditambahkan.');
    }

    public function edit($id)
    {
        return view('tanaman.edit', ['tanaman' => Tanaman::findOrFail($id), 'mejas' => Meja::all()]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nama_tanaman' => 'required|max:50',
            'id_meja' => 'nullable|exists:mejas,id_meja',
        ]);
        Tanaman::findOrFail($id)->update($data);

        return redirect()->route('tanaman.index')->with('success', 'Tanaman berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Tanaman::findOrFail($id)->delete();

        return redirect()->route('tanaman.index')->with('success', 'Tanaman berhasil dihapus.');
    }

    public function show($id)
    {
        return redirect()->route('tanaman.index');
    }
}
