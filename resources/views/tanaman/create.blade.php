@extends('layouts.app')
@section('title','Tambah Tanaman')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white"><h5 class="mb-0">🌱 Tambah Tanaman Baru</h5></div>
            <div class="card-body">
                <form action="{{ route('tanaman.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Tanaman</label>
                        <input type="text" name="nama_tanaman" class="form-control" value="{{ old('nama_tanaman') }}" placeholder="contoh: Selada Hijau" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Meja (opsional)</label>
                        <select name="id_meja" class="form-select">
                            <option value="">-- Pilih Meja --</option>
                            @foreach($mejas as $m)
                            <option value="{{ $m->id_meja }}" {{ old('id_meja') == $m->id_meja ? 'selected' : '' }}>{{ $m->meja }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Simpan</button>
                        <a href="{{ route('tanaman.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
