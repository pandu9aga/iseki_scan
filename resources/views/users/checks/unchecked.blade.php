@extends('layouts.user')

@section('style')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<style>
    .checked-box {
        appearance: none;
        -webkit-appearance: none;
        width: 18px;
        height: 18px;
        border: 2px solid #adb5bd;
        border-radius: 3px;
        background-color: #fff;
        cursor: default;
        vertical-align: middle;
        position: relative;
    }
    .checked-box:checked {
        background-color: #28a745;
        border-color: #28a745;
    }
    .checked-box:checked::after {
        content: "";
        position: absolute;
        left: 5px;
        top: 1px;
        width: 5px;
        height: 10px;
        border: solid #fff;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }
    .checked-box:disabled {
        opacity: 1;
    }
</style>
@endsection

@section('content')
@php
    $statusLabels = [
        'unchecked' => ['label' => 'Belum Check', 'badge' => 'badge-danger'],
        'checked'   => ['label' => 'Sudah Check', 'badge' => 'badge-success'],
        'all'       => ['label' => 'Semua', 'badge' => 'badge-secondary'],
    ];
    $statusMeta = $statusLabels[$status] ?? $statusLabels['unchecked'];
@endphp
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-clipboard-list mr-2"></i>Data Belum Check</h1>
    <p class="text-muted mb-3" style="font-size:0.85rem;">
        Harian &bull; Sumber "sudah check" = check otomatis (scan) &bull;
        Area mengikuti area akun Anda.
    </p>

    @if($errors->any())
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle mr-1"></i> {{ $errors->first() }}
    </div>
    @endif

    @if($noArea)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Area akun Anda belum di-assign oleh admin. Silakan hubungi admin untuk mengatur area
        (BARAT / TENGAH / TIMUR / PAINTING).
    </div>
    @else
    @php
        $s = $summary[$area] ?? ['total' => 0, 'checked' => 0, 'unchecked' => 0];
        $pct = $s['total'] > 0 ? round($s['checked'] / $s['total'] * 100, 1) : 0;
    @endphp

    {{-- Ringkasan area akun --}}
    <div class="card shadow-sm mb-3 border-left-primary">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                <div>
                    <h6 class="font-weight-bold text-primary mb-1">Area: {{ $area }}</h6>
                    <div class="small text-muted">
                        Tanggal: {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                    </div>
                </div>
                <span class="badge badge-danger font-weight-bold px-3 py-2">
                    Belum Check: {{ number_format($s['unchecked']) }}
                </span>
            </div>

            <div class="row mt-3">
                <div class="col-4 text-center">
                    <div class="h5 font-weight-bold mb-0">{{ number_format($s['total']) }}</div>
                    <div class="small text-muted">Total Part</div>
                </div>
                <div class="col-4 text-center">
                    <div class="h5 font-weight-bold text-success mb-0">{{ number_format($s['checked']) }}</div>
                    <div class="small text-muted">Sudah Check</div>
                </div>
                <div class="col-4 text-center">
                    <div class="h5 font-weight-bold text-danger mb-0">{{ number_format($s['unchecked']) }}</div>
                    <div class="small text-muted">Belum Check</div>
                </div>
            </div>

            <div class="progress mt-3" style="height:8px;">
                <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
            </div>
            <div class="text-right small text-muted mt-1">{{ $pct }}% sudah check</div>
        </div>
    </div>

    {{-- Filter tanggal & status --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('user.check.unchecked') }}" class="form-inline flex-wrap">
                <label class="mr-2 mb-2" style="font-size:0.82rem;">Tanggal:</label>
                <input type="date" name="date" class="form-control form-control-sm mr-3 mb-2" value="{{ $date }}">

                <label class="mr-2 mb-2" style="font-size:0.82rem;">Status:</label>
                <select name="status" class="form-control form-control-sm mr-3 mb-2">
                    <option value="unchecked" {{ $status === 'unchecked' ? 'selected' : '' }}>Belum Check</option>
                    <option value="checked" {{ $status === 'checked' ? 'selected' : '' }}>Sudah Check</option>
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua</option>
                </select>

                <button type="submit" class="btn btn-primary btn-sm mr-2 mb-2"><i class="fas fa-search"></i> Terapkan</button>
                <a href="{{ route('user.check.unchecked.export', ['date' => $date, 'status' => $status]) }}" class="btn btn-success btn-sm mb-2">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </form>
        </div>
    </div>

    {{-- Tabel part sesuai status --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
            <h6 class="m-0 font-weight-bold text-primary">{{ $statusMeta['label'] }} &mdash; {{ $area }}</h6>
            <span class="badge {{ $statusMeta['badge'] }} font-weight-bold px-3 py-2">
                {{ number_format(count($items)) }} part
            </span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="uncheckedTable" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>No</th>
                            <th>No Rack</th>
                            <th>Item Code</th>
                            <th>Nama Part</th>
                            <th>Cell</th>
                            <th>Zona</th>
                            <th class="text-center">Checked</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><code>{{ $item['rack_no'] }}</code></td>
                            <td>{{ $item['item_code'] }}</td>
                            <td>{{ $item['part_name'] }}</td>
                            <td>{{ $item['cell'] }}</td>
                            <td>{{ $item['zones'] }}</td>
                            <td class="text-center">
                                <input type="checkbox" class="checked-box" {{ $item['checked'] ? 'checked' : '' }} disabled>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('script')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(document).ready(function() {
    if ($('#uncheckedTable').length === 0) {
        return;
    }
    $('#uncheckedTable').DataTable({
        pageLength: 25,
        order: [[1, 'asc']],
        language: {
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data',
            zeroRecords: 'Tidak ada data sesuai filter',
            paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
        }
    });
});
</script>
@endsection
