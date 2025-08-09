@extends('layouts.user')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Request</h1>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col-xl-12">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Choose Day
                            </div>
                            <form action="{{ route('user_submission.submit') }}" method="GET">
                                @csrf
                                <div class="row d-flex align-items-center">
                                    <div class="col-lg-8 col-md-6 mb-1">
                                        <input name="Day_Request" type="date" class="form-control form-control-user" value="{{ $dateForInput }}">
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <button class="d-sm-inline btn btn-md btn-primary shadow-sm" type="submit">
                                            Apply
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('submission.export') }}" method="GET" target="_blank" class="mr-2">
            <input name="Day_Request_Hidden" type="hidden" value="{{ $dateForInput }}">
            <button class="d-sm-inline-block btn btn-md btn-primary shadow-sm" type="submit">
                <i class="fas fa-download fa-sm text-white-50"></i> Download Request
            </button>
        </form>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Request: {{ $formattedDate }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Item</th>
                            <th>Rack</th>
                            <th>Person</th>
                            <th>Sum Request</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>No</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Item</th>
                            <th>Rack</th>
                            <th>Person</th>
                            <th>Sum Request</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach ($submissions as $s)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $s->Day_Request }}</td>
                            <td>{{ $s->Time_Request }}</td>
                            <td>{{ $s->Code_Item_Rack }}</td>
                            <td>{{ $s->Code_Rack }}</td>
                            <td>{{ $s->member->Name_Member ?? '-' }}</td>
                            <td>{{ $s->Sum_Request }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('style')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endsection

@section('script')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('js/demo/datatables-demo.js') }}"></script>
@endsection
