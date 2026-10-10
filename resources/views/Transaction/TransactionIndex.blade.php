@extends('Layouts.App')

@section('content')
    @php
        $badge = ['success' => 'success', 'pending' => 'warning', 'failed' => 'danger'];
        $isFiltered = request()->anyFilled([
            'cari',
            'start_date',
            'end_date',
            'category_id',
            'status_id',
            'min_amount',
            'max_amount',
        ]);
    @endphp

    @php
        $exportQuery = http_build_query(request()->except('cursor'));
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Data Transaksi</h1>
        <div class="d-flex gap-2">
            <a href="/Transaction/export/csv?{{ $exportQuery }}" class="btn btn-outline-success btn-sm">Export CSV</a>
            <a href="/Transaction/export/excel?{{ $exportQuery }}" class="btn btn-success btn-sm">Export Excel</a>
        </div>
    </div>


    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="/Transaction">
                <div class="row g-2">
                    <div class="col-md-4 col-lg-3">
                        <label for="cari" class="form-label small mb-1">No. Referensi</label>
                        <input type="text" id="cari" name="cari" value="{{ request('cari') }}" maxlength="30"
                            class="form-control form-control-sm" placeholder="Awalan, contoh: TRX2610">
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="start_date" class="form-label small mb-1">Dari Tanggal</label>
                        <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}"
                            class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="end_date" class="form-label small mb-1">Sampai Tanggal</label>
                        <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}"
                            class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="category_id" class="form-label small mb-1">Kategori</label>
                        <select id="category_id" name="category_id" class="form-select form-select-sm">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <label for="status_id" class="form-label small mb-1">Status</label>
                        <select id="status_id" name="status_id" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}" @selected(request('status_id') == $status->id)>{{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <label for="min_amount" class="form-label small mb-1">Amount Minimal</label>
                        <input type="number" id="min_amount" name="min_amount" value="{{ request('min_amount') }}"
                            min="0" class="form-control form-control-sm" placeholder="0">
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <label for="max_amount" class="form-label small mb-1">Amount Maksimal</label>
                        <input type="number" id="max_amount" name="max_amount" value="{{ request('max_amount') }}"
                            min="0" class="form-control form-control-sm" placeholder="5000000">
                    </div>
                    <div class="col-md-4 col-lg-6 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        @if ($isFiltered)
                            <a href="/Transaction" class="btn btn-outline-secondary btn-sm">Reset</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Daftar Transaksi</span>
            <small class="text-muted">
                @if (!is_null($totalData))
                    {{ number_format($totalData, 0, ',', '.') }} transaksi
                @else
                    Jumlah total tidak dihitung untuk filter no. referensi / amount
                @endif
            </small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0 small">
                    <thead>
                        <tr>
                            <th>No. Referensi</th>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Metode</th>
                            <th class="text-end">Amount</th>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dataTransaction as $transaction)
                            <tr>
                                <td><code class="text-body">{{ $transaction->reference_no }}</code></td>
                                <td class="text-nowrap">{{ $transaction->transaction_date->format('d M Y H:i') }}</td>
                                <td>{{ $transaction->category->name ?? '-' }}</td>
                                <td>
                                    <span
                                        class="badge text-bg-{{ $badge[$transaction->status->code ?? ''] ?? 'secondary' }}">
                                        {{ $transaction->status->name ?? '-' }}
                                    </span>
                                </td>
                                <td>{{ $transaction->payment_method }}</td>
                                <td class="text-end text-nowrap">Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                </td>
                                <td>{{ $transaction->description }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    {{ $isFiltered ? 'Tidak ada transaksi yang sesuai dengan filter.' : 'Belum ada data transaksi.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($dataTransaction->hasPages())
            <div class="card-footer bg-white">
                {{ $dataTransaction->links() }}
            </div>
        @endif
    </div>
@endsection
