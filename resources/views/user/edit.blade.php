@extends('layouts.app')
@section('title','Edit User')
@section('content')
<div class="row justify-content-center"><div class="col-md-6">
    <div class="card shadow-sm">
        <div class="card-header bg-warning"><h5 class="mb-0">✏️ Edit User</h5></div>
        <div class="card-body">
            <form action="{{ route('user.update',$user->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-3"><label class="form-label fw-bold">Username</label><input type="text" name="username" class="form-control" value="{{ $user->username }}" required></div>
                <div class="mb-3"><label class="form-label fw-bold">Email</label><input type="email" name="email" class="form-control" value="{{ $user->email }}" required></div>
                <div class="mb-3"><label class="form-label fw-bold">Level</label><select name="level" class="form-select"><option value="user" {{ $user->level === 'user' ? 'selected':'' }}>User</option><option value="admin" {{ $user->level === 'admin' ? 'selected':'' }}>Admin</option></select></div>
                <hr><p class="text-muted small">Kosongkan password jika tidak ingin mengubahnya.</p>
                <div class="mb-3"><label class="form-label fw-bold">Password Baru</label><input type="password" name="password" class="form-control"></div>
                <div class="mb-3"><label class="form-label fw-bold">Konfirmasi Password</label><input type="password" name="password_confirmation" class="form-control"></div>
                <div class="d-flex gap-2"><button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update</button><a href="{{ route('user.index') }}" class="btn btn-secondary">Batal</a></div>
            </form>
        </div>
    </div>
</div></div>
@endsection
