@extends('layouts.app')
@section('title','Edit Siklus')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-warning"><h5 class="mb-0">✏️ Edit Siklus</h5></div>
            <div class="card-body">
                <form action="{{ route('siklus.update',$siklus->id_siklus) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3"><label class="form-label fw-bold">Tanaman</label>
                        <select name="id_tanaman" class="form-select"><option value="">-- Tidak ada --</option>@foreach($tanaman as $t)<option value="{{ $t->id_tanaman }}" {{ $siklus->id_tanaman == $t->id_tanaman ? 'selected':'' }}>{{ $t->nama_tanaman }}</option>@endforeach</select></div>
                    <div class="mb-3"><label class="form-label fw-bold">Nama Siklus</label><input type="text" name="nama_siklus" class="form-control" value="{{ $siklus->nama_siklus }}" required></div>
                    <div class="mb-3"><label class="form-label fw-bold">Waktu (Hari)</label><input type="number" name="waktu_siklus" class="form-control" value="{{ $siklus->waktu_siklus }}" required></div>
                    <div class="d-flex gap-2"><button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update</button><a href="{{ route('siklus.index') }}" class="btn btn-secondary">Batal</a></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
