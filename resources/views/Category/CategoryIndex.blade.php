@extends('Layouts.App')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Category</h1>
        <a href="/Category/create" class="btn btn-primary btn-sm">Tambah Category</a>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <form method="GET" action="/Category" class="d-flex gap-2">
                <input type="text" name="cari" value="{{ request('cari') }}" class="form-control form-control-sm"
                    placeholder="Cari kode atau nama...">
                <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                @if (request()->filled('cari'))
                    <a href="/Category" class="btn btn-outline-secondary btn-sm">Reset</a>
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
                    @forelse ($dataCategory as $category)
                        <tr>
                            <td>{{ $dataCategory->firstItem() + $loop->index }}</td>
                            <td><code>{{ $category->code }}</code></td>
                            <td>{{ $category->name }}</td>
                            <td class="text-end">
                                <a href="/Category/edit/{{ $category->uuid }}"
                                    class="btn btn-outline-primary btn-sm">Edit</a>
                                <form method="POST" action="/Category/delete/{{ $category->uuid }}" class="d-inline"
                                    onsubmit="return confirm('Yakin ingin menghapus category ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                @if (request()->filled('cari'))
                                    Category dengan kata kunci "{{ request('cari') }}" tidak ditemukan.
                                @else
                                    Belum ada data category.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($dataCategory->hasPages())
            <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    Menampilkan {{ $dataCategory->firstItem() }}–{{ $dataCategory->lastItem() }} dari
                    {{ $dataCategory->total() }} data
                </small>
                {{ $dataCategory->links() }}
            </div>
        @endif
    </div>
@endsection
