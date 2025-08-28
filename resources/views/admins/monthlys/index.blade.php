@extends('layouts.main')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">All Record</h1>
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <form class="user" action="{{ route('monthly.export') }}" method="GET" target="_blank">
            <input name="Day_Record_Hidden" type="hidden" class="form-control form-control-user" value="{{ $date }}">
            <button class="d-sm-inline-block btn btn-md btn-primary shadow-sm" type="submit">
                <i class="fas fa-download fa-sm text-white-50"></i> Download Record
            </button>
        </form>
        <button class="d-sm-inline-block btn btn-md btn-danger shadow-sm my-2" type="button" data-toggle="modal"
            data-target="#resetReportModal">
            <i class="fas fa-trash fa-sm text-white-50"></i> Reset Record
        </button>
        <!-- Logout Modal-->
        <div class="modal fade" id="resetReportModal" tabindex="-1" role="dialog" aria-labelledby="resetReportModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Reset Confirmation?</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div>Are you sure to reset records?</div>
                        <div>This action cannot be returned!</div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <a class="btn btn-danger" href="{{ route('monthly.reset') }}">Reset</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="row d-flex">
                <h6 class="m-0 font-weight-bold text-primary col-md-8">All Record</h6>
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
                            <th>Time Record</th>
                            <th>Area</th>
                            <th>Rack</th>
                            <th>Sum Record</th>
                            <th>Item</th>
                            <th>Name</th>
                            <th>Correctness</th>
                            <th>Time Request</th>
                            <th>Sum Request</th>
                            <th>Member</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>No</th>
                            <th>Time Record</th>
                            <th>Area</th>
                            <th>Rack</th>
                            <th>Sum Record</th>
                            <th>Item</th>
                            <th>Name</th>
                            <th>Correctness</th>
                            <th>Time Request</th>
                            <th>Sum Request</th>
                            <th>Member</th>
                            <th>Updated</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach ( $records as $r )
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $r->Day_Record }} {{ $r->Time_Record }}</td>
                            <td>{{ optional($r->request)->Area_Request ?? '' }}</td>
                            <td>{{ $r->Code_Rack }}</td>
                            <td>{{ $r->Sum_Record }}</td>
                            <td>{{ $r->Code_Item_Rack }}</td>
                            <td>{{ $r->rack->Name_Item_Rack ?? '' }}</td>
                            <td>
                                @if ($r->Correctness_Record == 1)
                                    <span class="text-white px-1 py-1 bg-gradient-success">
                                        Correct
                                    </span>
                                @else
                                    <span class="text-white px-1 py-1 bg-gradient-danger">
                                        Incorrect
                                    </span>
                                @endif
                            </td>
                            <td>{{ optional($r->request)->Day_Request ?? '' }} {{ optional($r->request)->Time_Request ?? '' }}</td>
                            <td>{{ optional($r->request)->Sum_Request ?? '' }}</td>
                            <td>{{ $r->member->Name_Member ?? '' }}</td>
                            <td>{{ $r->Updated_At_Record ?? '' }}</td>
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
