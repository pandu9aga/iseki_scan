@extends('layouts.main')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800">Ready - Waiting Requests</h1>
        <a href="{{ route('report', $dateForInput ? ['Day_Record' => $dateForInput] : []) }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm mr-1"></i>Back to Record
        </a>
    </div>

    {{-- Filter Card --}}
    <div class="card border-left-success shadow mb-4">
        <div class="card-body py-3">
            <div class="text-xs font-weight-bold text-success text-uppercase mb-2">
                Filter Day & Member
            </div>
            <form class="user" action="{{ route('report.ready_waiting') }}" method="GET" id="filterForm">
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
                        @if($dateForInput)
                            <button type="button" class="btn btn-sm btn-outline-secondary ml-1" onclick="clearDate()" title="Semua Tanggal">
                                <i class="fas fa-calendar-times"></i>
                            </button>
                        @endif
                    </div>

                    {{-- Member Filter --}}
                    <div style="min-width: 180px; max-width: 280px; flex: 1 1 180px;">
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
                        @if(request('Id_User') || request('Day_Record'))
                            <a href="{{ route('report.ready_waiting') }}" class="btn btn-sm btn-outline-secondary shadow-sm">
                                <i class="fas fa-undo fa-sm mr-1"></i>Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Export --}}
            <div class="d-flex flex-wrap align-items-center mt-2" style="gap: 8px;">
                <form class="user" action="{{ route('report.ready_waiting.export') }}" method="GET" target="_blank">
                    <input name="Day_Record" type="hidden" value="{{ $dateForInput }}">
                    <input name="Id_User" type="hidden" value="{{ request('Id_User') }}">
                    <button class="btn btn-sm btn-outline-success shadow-sm" type="submit">
                        <i class="fas fa-download fa-sm mr-1"></i>Download Excel
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Cards PIC Request --}}
    @php
        $colors = ['primary', 'success', 'info', 'warning', 'danger', 'dark', 'secondary'];
    @endphp
    @if($picSummary->isNotEmpty())
        <div class="row mb-3">
            @foreach($picSummary as $idx => $pic)
                @php
                    $color = $colors[$idx % count($colors)];
                @endphp
                <div class="col-xl-1 col-lg-3 col-md-4 col-sm-6 mb-1">
                    <div class="card border-left-{{ $color }} shadow h-100 py-1">
                        <div class="card-body py-1 px-2">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-{{ $color }} text-uppercase mb-1 text-truncate" title="{{ $pic['name'] }}">
                                        {{ $pic['name'] }}
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pic['count'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Data Table Card --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between" style="gap: 8px;">
            <h6 class="m-0 font-weight-bold text-success flex-shrink-0">
                List Requests: Ready & Waiting {{ $dateForInput ? '('. \Carbon\Carbon::parse($dateForInput)->locale('id')->isoFormat('dddd, D-MMM-YY') .')' : '' }}
            </h6>
            <div class="flex-shrink-0">
                <span class="badge badge-success px-2 py-1 font-weight-bold" style="font-size: 0.9rem;">
                    Total: {{ $totalRequests }}
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th>Time Req</th>
                            <th>Rack</th>
                            <th>Item</th>
                            <th>Name Part</th>
                            <th>Time Ready</th>
                            <th>PIC Req</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>No</th>
                            <th>Time Req</th>
                            <th>Rack</th>
                            <th>Item</th>
                            <th>Name Part</th>
                            <th>Time Ready</th>
                            <th>PIC Req</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach ($requests as $r)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $r->Day_Request }} {{ $r->Time_Request }}</td>
                            <td><span class="font-weight-bold">{{ $r->Code_Rack }}</span></td>
                            <td>{{ $r->Code_Item_Rack }}</td>
                            <td>{{ optional($r->rack)->Name_Item_Rack ?? '-' }}</td>
                            <td>
                                <span class="badge badge-success font-weight-normal px-2 py-1">
                                    <i class="fas fa-check-circle mr-1"></i>{{ $r->Ready_Request }}
                                </span>
                            </td>
                            <td>{{ $r->display_name ?? '-' }}</td>
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
    var currentVal = input.value;
    var d = currentVal ? new Date(currentVal + 'T00:00:00') : new Date();
    d.setDate(d.getDate() + offset);
    input.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    document.getElementById('filterForm').submit();
}

function setToday() {
    var d = new Date();
    document.getElementById('Day_Record').value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    document.getElementById('filterForm').submit();
}

function clearDate() {
    document.getElementById('Day_Record').value = '';
    document.getElementById('filterForm').submit();
}
</script>
@endsection
