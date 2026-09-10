@extends('layouts.app')
@section('title','Edit Pendewasaan')
@section('content')
<div class="row justify-content-center"><div class="col-md-7">
    <div class="card shadow-sm">
        <div class="card-header bg-warning"><h5 class="mb-0">✏️ Edit Pendewasaan</h5></div>
        <div class="card-body">
            <form action="{{ route('pendewasaan.update',$pendewasaan->id_pendewasaan) }}" method="POST">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label fw-bold">Dari Peremajaan</label>
                        <select name="id_peremajaan" class="form-select"><option value="">--</option>@foreach($peremajaans as $p)<option value="{{ $p->id_peremajaan }}" {{ $pendewasaan->id_peremajaan == $p->id_peremajaan ? 'selected':'' }}>Peremajaan #{{ $p->id_peremajaan }}</option>@endforeach</select></div>
                    <div class="col-md-6 mb-3"><label class="form-label fw-bold">Meja</label>
                        <select name="id_meja" class="form-select"><option value="">--</option>@foreach($mejas as $m)<option value="{{ $m->id_meja }}" {{ $pendewasaan->id_meja == $m->id_meja ? 'selected':'' }}>{{ $m->meja }}</option>@endforeach</select></div>
                    <div class="col-md-6 mb-3"><label class="form-label fw-bold">Tanaman Berhasil</label><input type="number" name="tanaman_berhasil" class="form-control" value="{{ $pendewasaan->tanaman_berhasil }}" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label fw-bold">Tanaman Gagal</label><input type="number" name="tanaman_gagal" class="form-control" value="{{ $pendewasaan->tanaman_gagal }}" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label fw-bold">Tgl Mulai</label><input type="date" name="tgl_awal_pendewasaan" class="form-control" value="{{ $pendewasaan->tgl_awal_pendewasaan }}" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label fw-bold">Tgl Selesai</label><input type="date" name="tgl_akhir_pendewasaan" class="form-control" value="{{ $pendewasaan->tgl_akhir_pendewasaan }}" required></div>
                    <div class="col-12 mb-3"><label class="form-label fw-bold">Keterangan</label><textarea name="keterangan" class="form-control" rows="2">{{ $pendewasaan->keterangan }}</textarea></div>
                </div>
                <div class="d-flex gap-2"><button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update</button><a href="{{ route('pendewasaan.index') }}" class="btn btn-secondary">Batal</a></div>
            </form>
        </div>
    </div>
</div></div>
@endsection
