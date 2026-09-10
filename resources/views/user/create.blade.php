@extends('layouts.app')
@section('title','Tambah User')
@section('content')
<div class="row justify-content-center"><div class="col-md-6">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white"><h5 class="mb-0">👤 Tambah User Baru</h5></div>
        <div class="card-body">
            <form action="{{ route('user.store') }}" method="POST">
                @csrf
                <div class="mb-3"><label class="form-label fw-bold">Username</label><input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" required>@error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label fw-bold">Email</label><input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label fw-bold">Password</label><input type="password" name="password" class="form-control" required></div>
                <div class="mb-3"><label class="form-label fw-bold">Konfirmasi Password</label><input type="password" name="password_confirmation" class="form-control" required></div>
                <div class="mb-3"><label class="form-label fw-bold">Level</label><select name="level" class="form-select"><option value="user">User</option><option value="admin">Admin</option></select></div>
                <div class="d-flex gap-2"><button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Simpan</button><a href="{{ route('user.index') }}" class="btn btn-secondary">Batal</a></div>
            </form>
        </div>
    </div>
</div></div>
@endsection
