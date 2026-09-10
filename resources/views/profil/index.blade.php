@extends('layouts.app')
@section('title','Profil Saya')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><h5 class="mb-0">👤 Profil Saya</h5></div>
            <div class="card-body">
                <form action="{{ route('profil.update') }}" method="POST">
                    @csrf
                    <div class="mb-3"><label class="form-label fw-bold">Username</label><input type="text" name="username" class="form-control" value="{{ $user->username }}" required></div>
                    <div class="mb-3"><label class="form-label fw-bold">Email</label><input type="email" name="email" class="form-control" value="{{ $user->email }}" required></div>
                    <div class="mb-3"><label class="form-label fw-bold">Level</label><input type="text" class="form-control" value="{{ ucfirst($user->level) }}" disabled></div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
                </form>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white"><h5 class="mb-0">🔐 Ganti Password</h5></div>
            <div class="card-body">
                <form action="{{ route('profil.password.update') }}" method="POST">
                    @csrf
                    <div class="mb-3"><label class="form-label fw-bold">Password Lama</label><input type="password" name="password_lama" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label fw-bold">Password Baru</label><input type="password" name="password" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label fw-bold">Konfirmasi Password</label><input type="password" name="password_confirmation" class="form-control" required></div>
                    <button type="submit" class="btn btn-secondary"><i class="fas fa-key me-1"></i>Ganti Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
