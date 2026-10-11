@extends('layouts.app')
@section('title', 'Edit Meja')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-warning text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-pen-to-square fs-4"></i>
                    <div>
                        <h5 class="mb-0">✏️ Edit Meja</h5>
                        <small class="opacity-75">Perbarui master data meja tanam</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('meja.update',$meja->id_meja) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3">
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-table-cells-large text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Data Meja</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Nama Meja <span class="text-danger">*</span></label>
                        <input type="text" name="meja" class="form-control @error('meja') is-invalid @enderror" value="{{ old('meja', $meja->meja) }}" required>@error('meja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Jumlah Lubang <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah_lubang" class="form-control @error('jumlah_lubang') is-invalid @enderror" value="{{ old('jumlah_lubang', $meja->jumlah_lubang) }}" min="1" required>@error('jumlah_lubang')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Kapasitas maksimal tanaman di meja ini</small>
                    </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('meja.index') }}" class="btn btn-outline-secondary px-4"><i class="fas fa-arrow-left me-1"></i>Batal</a>
                        <button type="submit" class="btn btn-warning px-4"><i class="fas fa-save me-1"></i>Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection