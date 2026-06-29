@extends('layouts.main')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">Record</h1>

    {{-- Filter Card --}}
    <div class="card border-left-primary shadow mb-4">
        <div class="card-body py-3">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-2">
                Choose Day
            </div>
            <form class="user" action="{{ route('report.submit') }}" method="GET" id="filterForm">
                @csrf
                <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                    {{-- Date Navigation --}}
                    <div class="d-flex align-items-center flex-shrink-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeDate(-1)" title="Sebelumnya">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <input name="Day_Record" id="Day_Record" type="date" class="form-control form-control-sm mx-1" style="width: auto; min-width: 140px;" value="{{ $dateForInput }}">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeDate(1)" title="Selanjutnya">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info ml-1" onclick="setToday()" title="Hari Ini">
                            <i class="fas fa-calendar-day"></i>
                        </button>
                    </div>

                    {{-- Member Filter --}}
                    <div style="min-width: 160px; max-width: 250px; flex: 1 1 160px;">
                        <select name="Id_User" class="form-control form-control-sm">
                            <option value="">All Members</option>
                            @foreach($members as $m)
                                <option value="{{ $m->id }}" 
                                    {{ request('Id_User') == $m->id ? 'selected' : '' }}>
                                    {{ $m->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex align-items-center flex-shrink-0" style="gap: 6px;">
                        <button class="btn btn-sm btn-primary shadow-sm" type="submit">
                            <i class="fas fa-filter fa-sm mr-1"></i>Apply
                        </button>
                    </div>
                </div>
            </form>

            {{-- Export --}}
            <form class="user mt-2" action="{{ route('report.export') }}" method="GET" target="_blank">
                <input name="Day_Record_Hidden" type="hidden" value="{{ $dateForInput }}">
                <input name="Id_User" type="hidden" value="{{ request('Id_User') }}">
                <button class="btn btn-sm btn-outline-primary shadow-sm" type="submit">
                    <i class="fas fa-download fa-sm mr-1"></i>Download Record
                </button>
            </form>
        </div>
    </div>

    {{-- Reset Modal (commented out) --}}
    {{-- <button class="d-sm-inline-block btn btn-md btn-danger shadow-sm mb-4" type="button" data-toggle="modal"
        data-target="#resetReportModal">
        <i class="fas fa-trash fa-sm text-white-50"></i> Reset Record
    </button> --}}
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

    {{-- Data Table --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: 8px;">
                <h6 class="m-0 font-weight-bold text-primary flex-shrink-0">Record: {{ $formattedDate }}</h6>
                <div class="d-flex flex-shrink-0" style="gap: 12px;">
                    <h6 class="m-0 font-weight-bold text-success">Correct: {{ $correct }}</h6>
                    <h6 class="m-0 font-weight-bold text-danger">Incorrect: {{ $incorrect }}</h6>
                </div>
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
                            <th>Type Tractor</th>
                            <th>Sum Record</th>
                            <th>Item</th>
                            <th>Name</th>
                            <th>Correctness</th>
                            <th>Time Request</th>
                            <th>Sum Request</th>
                            <th>Sum Stock</th>
                            <th>Estimation Date</th>
                            <th>Member Request</th>
                            <th>Member Record</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>No</th>
                            <th>Time Record</th>
                            <th>Area</th>
                            <th>Rack</th>
                            <th>Type Tractor</th>
                            <th>Sum Record</th>
                            <th>Item</th>
                            <th>Name</th>
                            <th>Correctness</th>
                            <th>Time Request</th>
                            <th>Sum Request</th>
                            <th>Sum Stock</th>
                            <th>Estimation Date</th>
                            <th>Member Request</th>
                            <th>Member Record</th>
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
                            <td title="{{ $r->rack->Type_Tractor_Rack ?? '-' }}">
                                {{ \Illuminate\Support\Str::limit($r->rack->Type_Tractor_Rack ?? '-', 20) }}
                            </td>
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
                            <td>
                                @if(optional($r->request)->Shipping_Request || optional($r->request)->Design_Changes_Request)
                                    {{ optional($r->request)->Stock_Shipping ?? '' }}
                                @else
                                    {{ optional($r->request)->Sum_Stock ?? '' }}
                                @endif
                            </td>
                            <td>{{ optional($r->request)->Estimation_Stock ? \Carbon\Carbon::parse($r->request->Estimation_Stock)->format('d/m/Y') : '-' }}</td>
                            <td>{{ optional($r->request)->display_name ?? '' }}</td>
                            <td>{{ $r->display_name ?? '' }}</td>
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
<script>
function changeDate(offset) {
    var input = document.getElementById('Day_Record');
    var d = new Date(input.value + 'T00:00:00');
    d.setDate(d.getDate() + offset);
    input.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    document.getElementById('filterForm').submit();
}

function setToday() {
    var d = new Date();
    document.getElementById('Day_Record').value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    document.getElementById('filterForm').submit();
}
</script>
@endsection