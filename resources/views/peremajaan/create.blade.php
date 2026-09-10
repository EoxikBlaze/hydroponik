@extends('layouts.app')
@section('title','Tambah Peremajaan')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white"><h5 class="mb-0">🌱 Tambah Data Peremajaan</h5></div>
            <div class="card-body">
                <form action="{{ route('peremajaan.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label fw-bold">Dari Semai</label>
                            <select name="id_semai" class="form-select"><option value="">-- Pilih Semai --</option>@foreach($semais as $s)<option value="{{ $s->id_semai }}">{{ $s->tanaman?->nama_tanaman ?? 'Semai #'.$s->id_semai }}</option>@endforeach</select></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-bold">Meja</label>
                            <select name="id_meja" class="form-select"><option value="">-- Pilih Meja --</option>@foreach($mejas as $m)<option value="{{ $m->id_meja }}">{{ $m->meja }}</option>@endforeach</select></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-bold">Benih Berhasil</label><input type="number" name="benih_berhasil" class="form-control" value="0" min="0" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-bold">Benih Gagal</label><input type="number" name="benih_gagal" class="form-control" value="0" min="0" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-bold">Tgl Mulai</label><input type="date" name="tgl_awal_peremajaan" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-bold">Tgl Selesai</label><input type="date" name="tgl_akhir_peremajaan" class="form-control" required></div>
                        <div class="col-12 mb-3"><label class="form-label fw-bold">Keterangan</label><textarea name="keterangan" class="form-control" rows="2"></textarea></div>
                    </div>
                    <div class="d-flex gap-2"><button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Simpan</button><a href="{{ route('peremajaan.index') }}" class="btn btn-secondary">Batal</a></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
