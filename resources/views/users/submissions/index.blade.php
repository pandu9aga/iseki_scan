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
                            <form class="user" action="{{ route('user_submission.submit') }}" method="GET">
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

    <a href="{{ route('request') }}">
        <button class="btn btn-lg btn-primary shadow-sm ms-auto mb-4" type="button">
            <i class="fas fa-bullhorn fa-sm text-white-50"></i> Request
        </button>
    </a>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Request: {{ $formattedDate }}</h6>
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
                            <th>No</th>
                            <th>Time Request</th>
                            <th>Time Record</th>
                            <th>Item</th>
                            <th>Rack</th>
                            <th>Sum Request</th>
                            <th>Sum Record</th>
                            <th>Member</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>No</th>
                            <th>Time Request</th>
                            <th>Time Record</th>
                            <th>Item</th>
                            <th>Rack</th>
                            <th>Sum Request</th>
                            <th>Sum Record</th>
                            <th>Member</th>
                            <th>Updated</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach ($submissions as $s)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $s->Day_Request }} {{ $s->Time_Request }}</td>
                            <td>{{ optional($s->record)->Day_Record ?? '' }} {{ optional($s->record)->Time_Record ?? '' }}</td>
                            <td>{{ $s->Code_Item_Rack }}</td>
                            <td>{{ $s->Code_Rack }}</td>
                            <td>
                                {{ $s->Sum_Request }}
                                <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#editModal{{ $s->Id_Request }}">
                                    Edit
                                </button>
                            </td>
                            <td>{{  optional($s->record)->Sum_Record ?? '' }}</td>
                            <td>{{ $s->member->Name_Member ?? '' }}</td>
                            <td>{{ $s->Updated_At_Request ?? '' }}</td>
                        </tr>
                        <div class="modal fade" id="editModal{{ $s->Id_Request }}" tabindex="-1" role="dialog" aria-labelledby="editModalLabel{{ $s->Id_Request }}" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <form method="POST" action="{{ route('submission.update', $s->Id_Request) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel{{ $s->Id_Request }}">Edit Request</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Jumlah Request</label>
                                                <input type="number" name="Sum_Request" class="form-control" value="{{ $s->Sum_Request }}" required min="1">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-success">Simpan</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
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
