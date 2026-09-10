@extends('layouts.user')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">Branch Recording</h1>
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <!-- Choose Day Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col-xl-12">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Pilih Tanggal
                            </div>
                            <form class="user" action="{{ route('branch_recording') }}" method="GET">
                                <div class="row d-flex align-items-center">
                                    <div class="col-lg-8 col-md-6 mb-1">
                                        <input name="Day_Branch_Record" type="date" class="form-control" value="{{ $date }}">
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <button class="d-sm-inline btn btn-md btn-primary shadow-sm" type="submit">
                                            Terapkan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form class="user" action="{{ route('branch_recording.export') }}" method="GET" target="_blank">
            <input name="Day_Branch_Record_Hidden" type="hidden" class="form-control form-control-user" value="{{ $date }}">
            <button class="d-sm-inline-block btn btn-md btn-success shadow-sm" type="submit">
                <i class="fas fa-file-excel fa-sm text-white-50 mr-1"></i> Download Excel
            </button>
        </form>
    </div>

    <a href="{{ route('branch_record') }}">
        <button class="btn btn-lg btn-primary shadow-sm ms-auto mb-4" type="button">
            <i class="fas fa-qrcode fa-sm text-white-50 mr-1"></i> Scan Branch Record
        </button>
    </a>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="row d-flex">
                <h6 class="m-0 font-weight-bold text-primary col-md-8">Hasil Catatan Rak: {{ $formattedDate }}</h6>
                <h6 class="m-0 font-weight-bold text-info col-md-4">Total: {{ $totalRecords }} Rak</h6>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <span class="badge bg-success">Success</span> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif

            @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <span class="badge bg-danger">Error</span> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Tanggal & Waktu</th>
                            <th>Kode Rak</th>
                            <th>Member / Operator</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th class="text-center">No</th>
                            <th>Tanggal & Waktu</th>
                            <th>Kode Rak</th>
                            <th>Member / Operator</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach ($records as $r)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $r->Day_Branch_Record }} {{ $r->Time_Branch_Record }}</td>
                            <td class="font-weight-bold text-primary">{{ $r->Code_Rack }}</td>
                            <td>{{ $r->display_name ?? '' }}</td>
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

@section('style')
<!-- Custom styles for this page -->
<link href="{{asset('vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
@endsection

@section('script')
<!-- Page level plugins -->
<script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "pageLength": 25,
            "ordering": true
        });
    });
</script>
@endsection