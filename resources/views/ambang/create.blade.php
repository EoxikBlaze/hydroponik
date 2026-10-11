@extends('layouts.app')
@section('title', 'Tambah Ambang Sensor')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-success text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-gauge-high fs-4"></i>
                    <div>
                        <h5 class="mb-0">📏 Tambah Ambang Sensor</h5>
                        <small class="opacity-75">Tentukan batas normal tiap sensor untuk peringatan alat</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('ambang.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                    @if(count($daftar) === 0)
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-circle-info me-1"></i>
                            Semua sensor sudah punya ambang — gunakan tombol <strong>Edit</strong> untuk mengubah nilainya.
                        </div>
                    </div>
                    @endif
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-gauge-high text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Nilai Ambang</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Sensor <span class="text-danger">*</span></label>
                        <select name="sensor" class="form-select" required>
                            <option value="">-- Pilih Sensor --</option>
                            @foreach($daftar as $key => $label)
                            <option value="{{ $key }}" {{ old('sensor') == $key ? 'selected':'' }}>{{ $label }}</option>
                            @endforeach
                        </select>@error('sensor')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <small class="text-muted">Hanya sensor yang belum punya ambang yang ditampilkan (anti dobel)</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Nilai Minimum <span class="text-danger">*</span></label>
                        <input type="number" name="min" class="form-control @error('min') is-invalid @enderror" value="{{ old('min') }}" step="any" required>@error('min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Nilai Maksimum <span class="text-danger">*</span></label>
                        <input type="number" name="max" class="form-control @error('max') is-invalid @enderror" value="{{ old('max') }}" step="any" required>@error('max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Harus lebih besar daripada nilai minimum</small>
                    </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('ambang.index') }}" class="btn btn-outline-secondary px-4"><i class="fas fa-arrow-left me-1"></i>Batal</a>
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection