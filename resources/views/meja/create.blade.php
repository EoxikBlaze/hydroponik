@extends('layouts.app')
@section('title','Tambah Meja')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white"><h5 class="mb-0">🪑 Tambah Meja Baru</h5></div>
            <div class="card-body">
                <form action="{{ route('meja.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Meja</label>
                        <input type="text" name="meja" class="form-control @error('meja') is-invalid @enderror" value="{{ old('meja') }}" placeholder="contoh: Meja Selada A" required>
                        @error('meja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Lubang</label>
                        <input type="number" name="jumlah_lubang" class="form-control @error('jumlah_lubang') is-invalid @enderror" value="{{ old('jumlah_lubang') }}" placeholder="contoh: 100" required min="1">
                        @error('jumlah_lubang')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Simpan</button>
                        <a href="{{ route('meja.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
