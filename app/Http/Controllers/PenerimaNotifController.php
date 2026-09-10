<?php
namespace App\Http\Controllers;
use App\Models\PenerimaNotif;
use Illuminate\Http\Request;

class PenerimaNotifController extends Controller
{
    public function index() {
        return view('penerima_notif.index', ['penerima' => PenerimaNotif::all()]);
    }
    public function create() {
        return view('penerima_notif.create');
    }
    public function store(Request $request) {
        $request->validate(['nama' => 'required|max:100', 'no_hp' => 'required|max:20']);
        PenerimaNotif::create($request->only('nama','no_hp'));
        return redirect()->route('penerima_notif.index')->with('success', 'Penerima berhasil ditambahkan.');
    }
    public function edit($id) {
        return view('penerima_notif.edit', ['data' => PenerimaNotif::findOrFail($id)]);
    }
    public function update(Request $request, $id) {
        $request->validate(['nama' => 'required|max:100', 'no_hp' => 'required|max:20']);
        PenerimaNotif::findOrFail($id)->update($request->only('nama','no_hp'));
        return redirect()->route('penerima_notif.index')->with('success', 'Penerima berhasil diperbarui.');
    }
    public function destroy($id) {
        PenerimaNotif::findOrFail($id)->delete();
        return redirect()->route('penerima_notif.index')->with('success','Kontak penerima berhasil dihapus.');
    }
    public function show($id) {
        return redirect()->route('penerima_notif.index');
    }
}
