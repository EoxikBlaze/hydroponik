@extends('layouts.app')
@section('title','Ganti Password')
@section('content')
<div class="row justify-content-center"><div class="col-md-5">
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white"><h5 class="mb-0">🔐 Ganti Password</h5></div>
        <div class="card-body">
            <form action="{{ route('profil.password.update') }}" method="POST">
                @csrf
                <div class="mb-3"><label class="form-label fw-bold">Password Lama</label><input type="password" name="password_lama" class="form-control @error('password_lama') is-invalid @enderror" required>@error('password_lama')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label fw-bold">Password Baru</label><input type="password" name="password" class="form-control" required></div>
                <div class="mb-3"><label class="form-label fw-bold">Konfirmasi</label><input type="password" name="password_confirmation" class="form-control" required></div>
                <div class="d-flex gap-2"><button type="submit" class="btn btn-secondary"><i class="fas fa-key me-1"></i>Ganti</button><a href="{{ route('profil.index') }}" class="btn btn-outline-secondary">Batal</a></div>
            </form>
        </div>
    </div>
</div></div>
@endsection
