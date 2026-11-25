@extends('layouts.main')

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
        Mapping area: <strong>BARAT = W</strong>, <strong>TENGAH = M</strong>, <strong>TIMUR = E</strong>, <strong>PAINTING = P</strong>
    </p>

    {{-- Ringkasan per area --}}
    <div class="row">
        @foreach($summary as $areaName => $s)
        @php
            $isActive = $areaName === $area;
            $pct = $s['total'] > 0 ? round($s['checked'] / $s['total'] * 100, 1) : 0;
        @endphp
        <div class="col-6 col-md-3 mb-3">
            <a href="{{ route('admin.check.unchecked', ['area' => $areaName, 'date' => $date, 'status' => $status]) }}" class="text-decoration-none">
                <div class="card shadow-sm h-100 border-left-{{ $isActive ? 'primary' : 'info' }}">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="font-weight-bold mb-0 {{ $isActive ? 'text-primary' : 'text-gray-700' }}">{{ $areaName }}</h6>
                            @if($isActive)<span class="badge badge-primary">Dipilih</span>@endif
                        </div>
                        <div class="small text-muted mb-2">Total {{ number_format($s['total']) }} part</div>
                        <div class="d-flex justify-content-between" style="font-size:0.82rem;">
                            <span class="text-success">Sudah: <strong>{{ number_format($s['checked']) }}</strong></span>
                            <span class="text-danger">Belum: <strong>{{ number_format($s['unchecked']) }}</strong></span>
                        </div>
                        <div class="progress mt-2" style="height:6px;">
                            <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="text-right small text-muted mt-1">{{ $pct }}%</div>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('admin.check.unchecked') }}" class="form-inline flex-wrap">
                <label class="mr-2 mb-2" style="font-size:0.82rem;">Area:</label>
                <select name="area" class="form-control form-control-sm mr-3 mb-2">
                    @foreach($summary as $areaName => $s)
                    <option value="{{ $areaName }}" {{ $area === $areaName ? 'selected' : '' }}>{{ $areaName }}</option>
                    @endforeach
                </select>

                <label class="mr-2 mb-2" style="font-size:0.82rem;">Tanggal:</label>
                <input type="date" name="date" class="form-control form-control-sm mr-3 mb-2" value="{{ $date }}">

                <label class="mr-2 mb-2" style="font-size:0.82rem;">Status:</label>
                <select name="status" class="form-control form-control-sm mr-3 mb-2">
                    <option value="unchecked" {{ $status === 'unchecked' ? 'selected' : '' }}>Belum Check</option>
                    <option value="checked" {{ $status === 'checked' ? 'selected' : '' }}>Sudah Check</option>
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua</option>
                </select>

                <button type="submit" class="btn btn-primary btn-sm mr-2 mb-2"><i class="fas fa-search"></i> Terapkan</button>
                <a href="{{ route('admin.check.unchecked.export', ['area' => $area, 'date' => $date, 'status' => $status]) }}" class="btn btn-success btn-sm mb-2">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </form>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
            <h6 class="m-0 font-weight-bold text-primary">
                Area {{ $area }} &mdash; {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
            </h6>
            <span class="badge {{ $statusMeta['badge'] }} font-weight-bold px-3 py-2">
                {{ $statusMeta['label'] }}: {{ number_format(count($items)) }}
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
</div>
@endsection

@section('script')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(document).ready(function() {
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
