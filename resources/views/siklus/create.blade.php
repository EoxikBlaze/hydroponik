@extends('layouts.app')
@section('title','Tambah Siklus')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white"><h5 class="mb-0">🔄 Tambah Siklus</h5></div>
            <div class="card-body">
                <form action="{{ route('siklus.store') }}" method="POST">
                    @csrf
                    <div class="mb-3"><label class="form-label fw-bold">Tanaman</label>
                        <select name="id_tanaman" class="form-select"><option value="">-- Pilih --</option>@foreach($tanaman as $t)<option value="{{ $t->id_tanaman }}">{{ $t->nama_tanaman }}</option>@endforeach</select></div>
                    <div class="mb-3"><label class="form-label fw-bold">Nama Siklus</label><input type="text" name="nama_siklus" class="form-control" placeholder="contoh: Semai" required></div>
                    <div class="mb-3"><label class="form-label fw-bold">Waktu (Hari)</label><input type="number" name="waktu_siklus" class="form-control" min="1" required></div>
                    <div class="d-flex gap-2"><button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Simpan</button><a href="{{ route('siklus.index') }}" class="btn btn-secondary">Batal</a></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
