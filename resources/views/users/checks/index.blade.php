@extends('layouts.user')

@section('style')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<style>
    .badge-mid { background-color: #fd7e14; color: #fff; }
    .badge-lot { background-color: #6f42c1; color: #fff; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-clipboard-check mr-2"></i>Check List</h1>

    {{-- Filter --}}
    <div class="card shadow-sm mb-3 border-left-primary">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('user.check') }}" class="form-inline d-flex align-items-center flex-wrap">
                <div class="mr-3 mb-2 mb-md-0 mt-2 mt-md-0 d-flex align-items-center">
                    <label class="mr-2 font-weight-bold text-gray-700" style="font-size:0.85rem;">
                        <i class="fas fa-calendar mr-1"></i>Tanggal:
                    </label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $dateForInput }}">
                </div>
                <div class="mr-3 mb-2 mb-md-0 mt-2 mt-md-0 d-flex align-items-center">
                    <label class="mr-2 text-gray-700" style="font-size:0.8rem;">Status:</label>
                    <select name="status" class="form-control form-control-sm" style="min-width: 120px;">
                        <option value="">Semua</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Sedang</option>
                        <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Banyak</option>
                    </select>
                </div>
                <div class="mt-2 mt-md-0">
                    <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search"></i> Terapkan</button>
                    <a href="{{ route('user.check') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-sync-alt"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Table --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="checkTable" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>No</th>
                            <th>Waktu</th>
                            <th>Code Rack</th>
                            <th>Code Item</th>
                            <th>Rack Name</th>
                            <th>Status</th>
                            <th>Checker</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($checks as $index => $c)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $c->Time_Check ? \Carbon\Carbon::parse($c->Time_Check)->format('d/m/y H:i') : '-' }}</td>
                            <td><code>{{ $c->Code_Rack }}</code></td>
                            <td>{{ $c->Code_Item_Rack }}</td>
                            <td>{{ $c->rack_name }}</td>
                            <td>
                                @if($c->Status_Check == 1)
                                    <span class="badge badge-mid">Sedang</span>
                                @elseif($c->Status_Check == 2)
                                    <span class="badge badge-lot">Banyak</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $c->checker_name }}</td>
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
    $('#checkTable').DataTable({
        pageLength: 25,
        order: [],
        language: {
            search: "Cari:", lengthMenu: "Tampilkan _MENU_ data",
            info: "Data _START_-_END_ dari _TOTAL_", infoEmpty: "Tidak ada data",
            zeroRecords: "Tidak ditemukan",
            emptyTable: '<div class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Belum ada data check.</div>',
            paginate: { next: "Selanjutnya", previous: "Sebelumnya" }
        }
    });
});
</script>
@endsection
