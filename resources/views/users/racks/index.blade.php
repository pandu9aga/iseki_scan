@extends('layouts.user')

@section('style')
<!-- Custom styles for DataTables -->
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<style>
    .table th,
    .table td {
        vertical-align: middle;
    }

    .badge-rack {
        font-size: 0.85rem;
        padding: 0.35em 0.65em;
    }
</style>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Data Rack</h1>
        </div>

    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table mr-1"></i> Master Rack & Part
            </h6>
            <span class="text-muted small">Total: {{ count($rack) }} Data</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Rack Code</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Type Tractor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rack as $i)
                        <tr>
                            <td class="text-center font-weight-bold">{{ $loop->iteration }}</td>
                            <td>
                                <span class="badge badge-light border text-dark font-weight-bold px-2 py-1">
                                    {{ $i->Code_Rack }}
                                </span>
                            </td>
                            <td class="font-weight-bold text-primary">{{ $i->Code_Item_Rack }}</td>
                            <td>{{ $i->Name_Item_Rack }}</td>
                            <td>
                                <span class="text-secondary small">{{ $i->Type_Tractor_Rack ?? '-' }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->
@endsection

@section('script')
<!-- Page level plugins -->
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@endsection