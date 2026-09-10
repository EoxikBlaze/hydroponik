<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash};

class ProfilController extends Controller
{
    public function index()  { return view('profil.index', ['user' => Auth::user()]); }
    public function formPassword() { return view('profil.password'); }

    public function update(Request $request)
    {
        $request->validate(['username' => 'required|max:50', 'email' => 'required|email|max:100']);
        Auth::user()->update($request->only('username','email'));
        return redirect()->route('profil.index')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate(['password_lama' => 'required', 'password' => 'required|min:6|confirmed']);
        if (! Hash::check($request->password_lama, Auth::user()->password)) {
            return back()->withErrors(['password_lama' => 'Password lama tidak cocok.']);
        }
        Auth::user()->update(['password' => Hash::make($request->password)]);
        return redirect()->route('profil.index')->with('success', 'Password berhasil diubah.');
    }
}
