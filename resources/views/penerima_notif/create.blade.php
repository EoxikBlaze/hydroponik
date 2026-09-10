@extends('layouts.app')
@section('title','Tambah Penerima Notif')
@section('content')
<div class="row justify-content-center"><div class="col-md-5">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white"><h5 class="mb-0">📱 Tambah Penerima Notifikasi</h5></div>
        <div class="card-body">
            <form action="{{ route('penerima_notif.store') }}" method="POST">
                @csrf
                <div class="mb-3"><label class="form-label fw-bold">Nama</label><input type="text" name="nama" class="form-control" value="{{ old('nama') }}" required></div>
                <div class="mb-3"><label class="form-label fw-bold">No HP WhatsApp</label>
                    <div class="input-group"><span class="input-group-text"><i class="fab fa-whatsapp"></i></span><input type="text" name="no_hp" class="form-control" value="{{ old('no_hp') }}" placeholder="628xxx / 08xxx" required></div>
                    <small class="text-muted">Format: 628xxxxxxx atau 08xxxxxxx</small></div>
                <div class="d-flex gap-2"><button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Simpan</button><a href="{{ route('penerima_notif.index') }}" class="btn btn-secondary">Batal</a></div>
            </form>
        </div>
    </div>
</div></div>
@endsection
