<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()  { return view('user.index', ['users' => User::all()]); }
    public function create() { return view('user.create'); }

    public function store(Request $request)
    {
        $request->validate(['username' => 'required|unique:users|max:50', 'email' => 'required|email|unique:users', 'password' => 'required|min:6|confirmed', 'level' => 'required|in:admin,user']);
        User::create(['username' => $request->username, 'email' => $request->email, 'password' => Hash::make($request->password), 'level' => $request->level]);
        return redirect()->route('user.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit($id) { return view('user.edit', ['user' => User::findOrFail($id)]); }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate(['username' => 'required|max:50|unique:users,username,'.$id, 'email' => 'required|email|unique:users,email,'.$id, 'level' => 'required|in:admin,user']);
        $data = $request->only('username','email','level');
        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6|confirmed']);
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);
        return redirect()->route('user.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy($id)
    {
        if ($id == auth()->id()) return back()->withErrors(['msg' => 'Tidak bisa hapus akun sendiri.']);
        User::findOrFail($id)->delete();
        return redirect()->route('user.index')->with('success', 'User dihapus.');
    }

    public function show($id) { return redirect()->route('user.index'); }
}
