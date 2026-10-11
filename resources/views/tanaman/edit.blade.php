@extends('layouts.app')
@section('title', 'Edit Tanaman')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-warning text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-pen-to-square fs-4"></i>
                    <div>
                        <h5 class="mb-0">✏️ Edit Jenis Tanaman</h5>
                        <small class="opacity-75">Perbarui master data jenis tanaman</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('tanaman.update',$tanaman->id_tanaman) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3">
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-carrot text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Data Tanaman</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Nama Tanaman <span class="text-danger">*</span></label>
                        <input type="text" name="nama_tanaman" class="form-control @error('nama_tanaman') is-invalid @enderror" value="{{ old('nama_tanaman', $tanaman->nama_tanaman) }}" maxlength="50" required>@error('nama_tanaman')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Maks. 50 karakter</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Meja (opsional)</label>
                        <select name="id_meja" class="form-select">
                            <option value="">-- Tanpa Meja --</option>
                            @foreach($mejas as $m)
                            <option value="{{ $m->id_meja }}" {{ old('id_meja', $tanaman->id_meja) == $m->id_meja ? 'selected':'' }}>{{ $m->meja }} ({{ $m->jumlah_lubang }} lubang)</option>
                            @endforeach
                        </select>
                    </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('tanaman.index') }}" class="btn btn-outline-secondary px-4"><i class="fas fa-arrow-left me-1"></i>Batal</a>
                        <button type="submit" class="btn btn-warning px-4"><i class="fas fa-save me-1"></i>Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection