@extends('layouts.app')
@section('title','Edit Semai')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-warning"><h5 class="mb-0">✏️ Edit Data Semai</h5></div>
            <div class="card-body">
                <form action="{{ route('semai.update',$semai->id_semai) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tanaman</label>
                            <select name="id_tanaman" class="form-select" required>
                                @foreach($tanaman as $t)
                                <option value="{{ $t->id_tanaman }}" {{ $semai->id_tanaman == $t->id_tanaman ? 'selected':'' }}>{{ $t->nama_tanaman }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Siklus</label>
                            <select name="id_siklus" class="form-select">
                                <option value="">-- Tidak ada --</option>
                                @foreach($siklus as $sk)
                                <option value="{{ $sk->id_siklus }}" {{ $semai->id_siklus == $sk->id_siklus ? 'selected':'' }}>{{ $sk->nama_siklus }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Jumlah Benih</label>
                            <input type="number" name="jumlah_benih" class="form-control" value="{{ $semai->jumlah_benih }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Benih Berhasil</label>
                            <input type="number" name="benih_berhasil" class="form-control" value="{{ $semai->benih_berhasil }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Benih Gagal</label>
                            <input type="number" name="benih_gagal" class="form-control" value="{{ $semai->benih_gagal }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Tgl Awal</label>
                            <input type="date" name="tgl_awal_semai" class="form-control" value="{{ $semai->tgl_awal_semai }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Tgl Selesai</label>
                            <input type="date" name="tgl_akhir_semai" class="form-control" value="{{ $semai->tgl_akhir_semai }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                @foreach(['proses','selesai','gagal'] as $st)
                                <option value="{{ $st }}" {{ $semai->status == $st ? 'selected':'' }}>{{ ucfirst($st) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2">{{ $semai->keterangan }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update</button>
                        <a href="{{ route('semai.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
