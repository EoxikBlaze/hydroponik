@extends('layouts.app')
@section('title', 'Catat Panen')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-success text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-wheat-awn fs-4"></i>
                    <div>
                        <h5 class="mb-0">🌾 Catat Hasil Panen</h5>
                        <small class="opacity-75">Pencatatan hasil panen sayuran hidroponik</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('panen.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-link text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Sumber Tanaman</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Dari Pendewasaan <span class="text-danger">*</span></label>
                        <select name="id_pendewasaan" class="form-select" required>
                            <option value="">-- Pilih Pendewasaan --</option>
                            @foreach($pendewasaans as $p)
                            <option value="{{ $p->id_pendewasaan }}" {{ old('id_pendewasaan', request('id_pendewasaan')) == $p->id_pendewasaan ? 'selected':'' }}>{{ trim(($p->peremajaan?->semai?->tanaman?->nama_tanaman ?? 'Tanaman ?') . ' — Meja ' . ($p->meja?->meja ?? '-') . ' — ' . $p->tanaman_berhasil . ' tanaman') }}</option>
                            @endforeach
                        </select>@error('id_pendewasaan')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-wheat-awn text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Hasil Panen</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Panen Berhasil <span class="text-danger">*</span></label>
                        <input type="number" name="panen_berhasil" class="form-control @error('panen_berhasil') is-invalid @enderror" value="{{ old('panen_berhasil', 0) }}" min="0" required>@error('panen_berhasil')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Panen Gagal <span class="text-danger">*</span></label>
                        <input type="number" name="panen_gagal" class="form-control @error('panen_gagal') is-invalid @enderror" value="{{ old('panen_gagal', 0) }}" min="0" required>@error('panen_gagal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tanggal Panen <span class="text-danger">*</span></label>
                        <input type="date" name="tgl_panen" class="form-control @error('tgl_panen') is-invalid @enderror" value="{{ old('tgl_panen', date('Y-m-d')) }}" required>@error('tgl_panen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Opsional...">{{ old('keterangan') }}</textarea>
                    </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('panen.index') }}" class="btn btn-outline-secondary px-4"><i class="fas fa-arrow-left me-1"></i>Batal</a>
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection