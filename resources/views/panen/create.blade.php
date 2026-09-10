@extends('layouts.app')
@section('title','Catat Panen')
@section('content')
<div class="row justify-content-center"><div class="col-md-6">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white"><h5 class="mb-0">🌾 Catat Hasil Panen</h5></div>
        <div class="card-body">
            <form action="{{ route('panen.store') }}" method="POST">
                @csrf
                <div class="mb-3"><label class="form-label fw-bold">Dari Pendewasaan</label>
                    <select name="id_pendewasaan" class="form-select" required><option value="">-- Pilih --</option>@foreach($pendewasaans as $p)<option value="{{ $p->id_pendewasaan }}" {{ request('id_pendewasaan') == $p->id_pendewasaan ? 'selected':'' }}>Pendewasaan #{{ $p->id_pendewasaan }} - {{ $p->meja?->meja }}</option>@endforeach</select></div>
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label fw-bold">Panen Berhasil</label><input type="number" name="panen_berhasil" class="form-control" value="0" min="0" required></div>
                    <div class="col-6 mb-3"><label class="form-label fw-bold">Panen Gagal</label><input type="number" name="panen_gagal" class="form-control" value="0" min="0" required></div>
                </div>
                <div class="mb-3"><label class="form-label fw-bold">Tanggal Panen</label><input type="date" name="tgl_panen" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                <div class="mb-3"><label class="form-label fw-bold">Keterangan</label><textarea name="keterangan" class="form-control" rows="2"></textarea></div>
                <div class="d-flex gap-2"><button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Simpan</button><a href="{{ route('panen.index') }}" class="btn btn-secondary">Batal</a></div>
            </form>
        </div>
    </div>
</div></div>
@endsection
