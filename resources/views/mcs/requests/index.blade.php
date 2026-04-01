@extends('layouts.mc')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Request</h1>
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
    <div class="d-sm-flex align-items-center justify-content-between mb-1">
        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-8 col-md-6 mb-4">
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
                                    <div class="col-lg-3 col-md-6 mb-1">
                                        <select name="Id_User[]" class="form-control" multiple>
                                            <option value="">All Members</option>
                                            @foreach($members as $m)
                                            <option value="{{ $m->Id_Member }}"
                                                {{ in_array($m->Id_Member, request('Id_User', [])) ? 'selected' : '' }}>
                                                {{ $m->Name_Member }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-1">
                                        <select name="statusFilter" class="form-control">
                                            <option value="">All Status</option>
                                            <option value="ready" {{ request('statusFilter') == 'ready' ? 'selected' : '' }}>Ready</option>
                                            <option value="shipping" {{ request('statusFilter') == 'shipping' ? 'selected' : '' }}>Shipping</option>
                                            <option value="production" {{ request('statusFilter') == 'production' ? 'selected' : '' }}>Production</option>
                                            <option value="design_change" {{ request('statusFilter') == 'design_change' ? 'selected' : '' }}>Design Change</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-6">
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
        <a class="d-sm-inline-block btn btn-md btn-info shadow-sm" href="{{ route('mc_submission.search') }}">
            <i class="fas fa-search fa-sm text-white-50"></i> Advanced Search
        </a>
        <form class="user my-2" action="{{ route('mc_submission.export') }}" method="GET" target="_blank">
            <input name="Day_Request_Hidden" type="hidden" value="{{ $dateForInput }}">
            @foreach(request('Id_User', []) as $id)
            <input type="hidden" name="Id_User[]" value="{{ $id }}">
            @endforeach
            <input type="hidden" name="statusFilter" value="{{ request('statusFilter') }}">
            <button class="d-sm-inline-block btn btn-md btn-primary shadow-sm" type="submit">
                <i class="fas fa-download fa-sm text-white-50"></i> Download Report
            </button>
        </form>
    </div>

    <div class="card mb-4 col-md-4 col-lg-3">
        <div class="card-header">
            <div class="font-weight-bold text-primary text-uppercase">
                Update Ready Stock
            </div>
        </div>
        <div class="card-body">
            <div>
                <form action="{{ route('mc_submission.upload_ready') }}" method="POST" enctype="multipart/form-data" class="d-inline">
                    @csrf
                    <div class="input-group">
                        <input type="file" name="ready_excel" class="form-control" accept=".xlsx,.xls" required>
                        <button class="btn btn-success ml-1" type="submit">Upload</button>
                    </div>
                    @if ($errors->has('ready_excel'))
                    <div class="text-danger mt-1">{{ $errors->first('ready_excel') }}</div>
                    @endif
                </form>
            </div>
        </div>
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
                            <th>Type Tractor</th>
                            <th>Sum Request</th>
                            <th>Urgenity</th>
                            <th>Item</th>
                            <th>Name</th>
                            <th>Ready Stock</th>
                            <th>Estimation Date</th>
                            <th>Sum Stock</th>
                            <th>OK Stock</th>
                            <th>Time Record</th>
                            <th>Sum Record</th>
                            <th>Member Request</th>
                            <th>Member Record</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>No</th>
                            <th>Time Request</th>
                            <th>Area</th>
                            <th>Rack</th>
                            <th>Type Tractor</th>
                            <th>Sum Request</th>
                            <th>Urgenity</th>
                            <th>Item</th>
                            <th>Name</th>
                            <th>Ready Stock</th>
                            <th>Estimation Date</th>
                            <th>Sum Stock</th>
                            <th>OK Stock</th>
                            <th>Time Record</th>
                            <th>Sum Record</th>
                            <th>Member Request</th>
                            <th>Member Record</th>
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
                            <td title="{{ $s->rack->Type_Tractor_Rack ?? '-' }}">
                                {{ \Illuminate\Support\Str::limit($s->rack->Type_Tractor_Rack ?? '-', 20) }}
                            </td>
                            <td>{{ $s->Sum_Request }}</td>
                            <td class="text-center">{{ $s->Urgent_Request == 1 ? '✓' : '' }}</td>
                            <td>{{ $s->Code_Item_Rack }}</td>
                            <td>{{ $s->rack->Name_Item_Rack ?? '' }}</td>
                            <td>
                                @php
                                $statuses = [];
                                if ($s->Ready_Request) $statuses[] = '<span class="badge badge-success">Ready</span>:' . $s->Ready_Request . '</span>';
                                if ($s->Shipping_Request) $statuses[] = '<span class="badge badge-info">Shipping</span>:' . $s->Shipping_Request;
                                if ($s->Production_Area_Request) $statuses[] = '<span class="badge badge-primary">Production</span>:' . $s->Production_Area_Request;
                                if ($s->Design_Changes_Request) $statuses[] = '<span class="badge badge-warning">Design Change</span>:' . $s->Design_Changes_Request;
                                echo implode(' | ', $statuses);
                                @endphp
                            </td>
                            <td class="text-center">
                                @if($s->Estimation_Stock)
                                    {{ \Carbon\Carbon::parse($s->Estimation_Stock)->format('d/m/Y') }}
                                @endif
                            </td>
                            <td>{{ $s->Sum_Stock ?? '' }}</td>
                            <td class="text-center">
                                @if($s->Shipping_Request || $s->Design_Changes_Request)
                                    @if($s->Ok_Stock == 1)
                                        <span class="badge badge-success">OK</span>
                                    @else
                                        <form action="{{ route('mc_submission.ok_stock', $s->Id_Request) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin OK Stock?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-check"></i> OK
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                            <td>{{ optional($s->record)->Day_Record ?? '' }} {{ optional($s->record)->Time_Record ?? '' }}</td>
                            <td>{{ optional($s->record)->Sum_Record ?? '' }}</td>
                            <td>{{ $s->member->Name_Member ?? '' }}</td>
                            <td>{{ optional($s->record)->member->Name_Member ?? '' }}</td>
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
<link href="{{ asset('css/select2.min.css') }}" rel="stylesheet">
@endsection

@section('script')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('js/demo/datatables-demo.js') }}"></script>
<script src="{{ asset('js/select2.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('select[multiple]').select2({
            placeholder: "Pilih member...",
            allowClear: false
        });
    });
</script>
@endsection