@extends('Layouts.App')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Import Data</h1>
        <a href="/Import/create" class="btn btn-primary btn-sm">Upload CSV</a>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <form method="GET" action="/Import" class="d-flex flex-wrap gap-2">
                <input type="text" name="cari" value="{{ request('cari') }}"
                    class="form-control form-control-sm w-auto flex-grow-1" placeholder="Cari nama file...">
                <select name="status" class="form-select form-select-sm w-auto">
                    <option value="">Semua Status</option>
                    @foreach (['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'failed' => 'Failed'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                @if (request()->filled('cari') || request()->filled('status'))
                    <a href="/Import" class="btn btn-outline-secondary btn-sm">Reset</a>
                @endif
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px">No</th>
                            <th>File</th>
                            <th>Diupload Oleh</th>
                            <th>Status</th>
                            <th style="min-width: 200px">Progress</th>
                            <th class="text-end">Gagal</th>
                            <th>Waktu Upload</th>
                            <th class="text-end">Aksi</th>

                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $badge = [
                                'pending' => 'secondary',
                                'processing' => 'primary',
                                'completed' => 'success',
                                'failed' => 'danger',
                            ];
                        @endphp
                        @forelse ($dataImport as $import)
                            @php
                                $percent =
                                    $import->total_rows > 0 ? ($import->processed_rows / $import->total_rows) * 100 : 0;
                            @endphp
                            <tr>
                                <td>{{ $dataImport->firstItem() + $loop->index }}</td>
                                <td>{{ $import->file_name }}</td>
                                <td>{{ $import->user->name ?? '-' }}</td>
                                <td><span
                                        class="badge text-bg-{{ $badge[$import->status] ?? 'secondary' }}">{{ ucfirst($import->status) }}</span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 6px">
                                        <div class="progress-bar" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <small class="text-muted">
                                        {{ number_format($import->processed_rows, 0, ',', '.') }} /
                                        {{ number_format($import->total_rows, 0, ',', '.') }}
                                        ({{ number_format($percent, 1, ',', '.') }}%)
                                    </small>
                                </td>
                                <td class="text-end {{ $import->failed_rows > 0 ? 'text-danger' : '' }}">
                                    {{ number_format($import->failed_rows, 0, ',', '.') }}
                                </td>
                                <td>{{ $import->created_at->format('d M Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="/Import/detail/{{ $import->id }}"
                                        class="btn btn-outline-primary btn-sm">Detail</a>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    @if (request()->filled('cari') || request()->filled('status'))
                                        Import yang dicari tidak ditemukan.
                                    @else
                                        Belum ada riwayat import.
                                    @endif
                                </td>

                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($dataImport->hasPages())
            <div class="card-footer bg-white">
                {{ $dataImport->links() }}
            </div>
        @endif
    </div>
    @if ($dataImport->contains(fn($import) => in_array($import->status, ['pending', 'processing'])))
        @push('scripts')
            <script src="{{ asset('js/import-index.js') }}?v={{ filemtime(public_path('js/import-index.js')) }}"></script>
        @endpush
    @endif
@endsection
