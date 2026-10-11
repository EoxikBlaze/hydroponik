@extends('layouts.app')
@section('title', 'Tambah Durasi Siklus')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-success text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-clock-rotate-left fs-4"></i>
                    <div>
                        <h5 class="mb-0">⏱ Tambah Durasi Siklus</h5>
                        <small class="opacity-75">Master durasi tiap tahap — dasar hitung tanggal selesai otomatis</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('siklus.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-clock-rotate-left text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Durasi Siklus</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tanaman (opsional)</label>
                        <select name="id_tanaman" class="form-select">
                            <option value="">-- Semua / Umum --</option>
                            @foreach($tanaman as $t)
                            <option value="{{ $t->id_tanaman }}" {{ old('id_tanaman') == $t->id_tanaman ? 'selected':'' }}>{{ $t->nama_tanaman }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Nama Siklus <span class="text-danger">*</span></label>
                        <input type="text" name="nama_siklus" class="form-control @error('nama_siklus') is-invalid @enderror" value="{{ old('nama_siklus') }}" maxlength="100" required>@error('nama_siklus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Mis. "Semai", "Peremajaan", "Pendewasaan"</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Durasi (hari) <span class="text-danger">*</span></label>
                        <input type="number" name="waktu_siklus" class="form-control @error('waktu_siklus') is-invalid @enderror" value="{{ old('waktu_siklus') }}" min="1" required>@error('waktu_siklus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Dipakai untuk hitung tgl selesai otomatis di form tahapan</small>
                    </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('siklus.index') }}" class="btn btn-outline-secondary px-4"><i class="fas fa-arrow-left me-1"></i>Batal</a>
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection