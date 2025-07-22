@extends('layouts.user')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">Home</h1>
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-end justify-content-between mb-4">
        <div></div>
        <a href="{{ route('record') }}">
            <button class="d-sm-inline-block btn btn-md btn-primary shadow-sm ms-auto" type="button">
                <i class="fas fa-qrcode fa-sm text-white-50"></i> Record
            </button>
        </a>
    </div>
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="row d-flex">
                <h6 class="m-0 font-weight-bold text-primary col-md-8">Report: {{ $formattedDate }}</h6>
                <h6 class="m-0 font-weight-bold text-success col-md-2">Correct: {{ $correct }}</h6>
                <h6 class="m-0 font-weight-bold text-danger col-md-2">Incorrect: {{ $incorrect }}</h6>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Time</th>
                            <th>Item</th>
                            <th>Rack</th>
                            <th>Correctness</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>No</th>
                            <th>Time</th>
                            <th>Item</th>
                            <th>Rack</th>
                            <th>Correctness</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach ( $records as $i )
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $i->Time_Record }}</td>
                            <td>{{ $i->Code_Item_Rack }}</td>
                            <td>{{ $i->Code_Rack }}</td>
                            <td>
                                @if ($i->Correctness_Record == 1)
                                    <span class="text-white px-1 py-1 bg-gradient-success">
                                        Correct
                                    </span>
                                @else
                                    <span class="text-white px-1 py-1 bg-gradient-danger">
                                        Incorrect
                                    </span>
                                @endif
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

@section('style')
<!-- Custom styles for this page -->
<link href="{{asset('vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
@endsection

@section('script')
<!-- Page level plugins -->
<script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>

<!-- Page level custom scripts -->
<script src="{{asset('js/demo/datatables-demo.js')}}"></script>
@endsection