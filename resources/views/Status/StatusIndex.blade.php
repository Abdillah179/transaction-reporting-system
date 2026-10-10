@extends('Layouts.App')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Data Status</h1>
        <a href="/Status/create" class="btn btn-primary btn-sm">Tambah Status</a>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <form method="GET" action="/Status" class="d-flex gap-2">
                <input type="text" name="cari" value="{{ request('cari') }}" class="form-control form-control-sm"
                    placeholder="Cari kode atau nama...">
                <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                @if (request()->filled('cari'))
                    <a href="/Status" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </form>
        </div>

        <div class="card-body p-0">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px">No</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dataStatus as $status)
                        <tr>
                            <td>{{ $dataStatus->firstItem() + $loop->index }}</td>
                            <td>
                                <code>{{ $status->code }}</code>
                                @if (in_array($status->code, \App\Models\Status::SYSTEM_CODES))
                                    <span class="badge text-bg-secondary ms-1">Default</span>
                                @endif
                            </td>
                            <td>{{ $status->name }}</td>
                            <td class="text-end">
                                <a href="/Status/edit/{{ $status->uuid }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                @unless (in_array($status->code, \App\Models\Status::SYSTEM_CODES))
                                    <form method="POST" action="/Status/delete/{{ $status->uuid }}" class="d-inline"
                                        onsubmit="return confirm('Yakin ingin menghapus status {{ $status->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                @if (request()->filled('cari'))
                                    Status dengan kata kunci "{{ request('cari') }}" tidak ditemukan.
                                @else
                                    Belum ada data status.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($dataStatus->hasPages())
            <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    Menampilkan {{ $dataStatus->firstItem() }}–{{ $dataStatus->lastItem() }} dari
                    {{ $dataStatus->total() }} data
                </small>
                {{ $dataStatus->links() }}
            </div>
        @endif
    </div>
@endsection
