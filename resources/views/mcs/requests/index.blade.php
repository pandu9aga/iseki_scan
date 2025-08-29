@extends('layouts.mc')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Request</h1>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col-xl-12">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Choose Day
                            </div>
                            <form class="user" action="{{ route('mc_submission.submit') }}" method="GET">
                                @csrf
                                <div class="row d-flex align-items-center">
                                    <div class="col-lg-4 col-md-6 mb-1">
                                        <input name="Day_Request" type="date" class="form-control" value="{{ $dateForInput }}" required>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-1">
                                        <select name="Id_User" class="form-control">
                                            <option value="">All Members</option>
                                            @foreach($members as $m)
                                                <option value="{{ $m->Id_Member }}" 
                                                    {{ request('Id_User') == $m->Id_Member ? 'selected' : '' }}>
                                                    {{ $m->Name_Member }}
                                                </option>
                                            @endforeach
                                        </select>
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
        <form class="user" action="{{ route('mc_submission.export') }}" method="GET" target="_blank">
            <input name="Day_Request_Hidden" type="hidden" value="{{ $dateForInput }}">
            <input name="Id_User" type="hidden" value="{{ request('Id_User') }}">
            <button class="d-sm-inline-block btn btn-md btn-primary shadow-sm" type="submit">
                <i class="fas fa-download fa-sm text-white-50"></i> Download Report
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
                            <th>Time Request</th>
                            <th>Area</th>
                            <th>Rack</th>
                            <th>Sum Request</th>
                            <th>Urgenity</th>
                            <th>Item</th>
                            <th>Name</th>
                            {{-- <th>Time Record</th>
                            <th>Sum Record</th> --}}
                            <th>Member</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>No</th>
                            <th>Time Request</th>
                            <th>Area</th>
                            <th>Rack</th>
                            <th>Sum Request</th>
                            <th>Urgenity</th>
                            <th>Item</th>
                            <th>Name</th>
                            {{-- <th>Time Record</th>
                            <th>Sum Record</th> --}}
                            <th>Member</th>
                            <th>Updated</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach ($requests as $s)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $s->Day_Request }} {{ $s->Time_Request }}</td>
                            <td>{{ $s->Area_Request ?? '' }}</td>
                            <td>{{ $s->Code_Rack }}</td>
                            <td>{{ $s->Sum_Request }}</td>
                            <td class="text-center">{{ $s->Urgent_Request == 1 ? '✓' : '' }}</td>
                            <td>{{ $s->Code_Item_Rack }}</td>
                            <td>{{ $s->rack->Name_Item_Rack ?? '' }}</td>
                            {{-- <td>{{ optional($s->record)->Day_Record ?? '' }} {{ optional($s->record)->Time_Record ?? '' }}</td>
                            <td>{{  optional($s->record)->Sum_Record ?? '' }}</td> --}}
                            <td>{{ $s->member->Name_Member ?? '' }}</td>
                            <td>{{ $s->Updated_At_Request ?? '' }}</td>
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
