@extends('layouts.main')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Request</h1>
    <div class="card border-left-primary shadow mb-4">
        <div class="card-body py-3">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-2">
                Choose Day
            </div>
            <form class="user" action="{{ route('request.submit') }}" method="GET" id="filterForm">
                @csrf
                <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                    {{-- Date Navigation --}}
                    <div class="d-flex align-items-center flex-shrink-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeDate(-1)" title="Sebelumnya">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <input name="Day_Request" id="Day_Request" type="date" class="form-control form-control-sm mx-1" style="width: auto; min-width: 140px;" value="{{ $dateForInput }}" required>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeDate(1)" title="Selanjutnya">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info ml-1" onclick="setToday()" title="Hari Ini">
                            <i class="fas fa-calendar-day"></i>
                        </button>
                    </div>

                    {{-- Member Filter --}}
                    <div style="min-width: 160px; max-width: 250px; flex: 1 1 160px;">
                        <select name="Id_User[]" class="form-control form-control-sm" multiple>
                            <option value="">All Members</option>
                            @foreach($members as $m)
                                <option value="{{ $m->id }}" 
                                    {{ in_array($m->id, request('Id_User', [])) ? 'selected' : '' }}>
                                    {{ $m->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status Filter --}}
                    <div style="min-width: 130px; max-width: 180px; flex: 1 1 130px;">
                        <select name="statusFilter" class="form-control form-control-sm">
                            <option value="">All Status</option>
                            <option value="ready" {{ request('statusFilter') == 'ready' ? 'selected' : '' }}>Ready</option>
                            <option value="shipping" {{ request('statusFilter') == 'shipping' ? 'selected' : '' }}>Shipping</option>
                            <option value="production" {{ request('statusFilter') == 'production' ? 'selected' : '' }}>Production</option>
                            <option value="design_change" {{ request('statusFilter') == 'design_change' ? 'selected' : '' }}>Design Change</option>
                        </select>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex align-items-center flex-shrink-0" style="gap: 6px;">
                        <button class="btn btn-sm btn-primary shadow-sm" type="submit">
                            <i class="fas fa-filter fa-sm mr-1"></i>Apply
                        </button>
                        <a class="btn btn-sm btn-info shadow-sm" href="{{ route('request.search') }}">
                            <i class="fas fa-search fa-sm mr-1"></i>Advanced Search
                        </a>
                    </div>
                </div>
            </form>

            {{-- Export --}}
            <form class="user mt-2" action="{{ route('request.export') }}" method="GET" target="_blank">
                <input name="Day_Request_Hidden" type="hidden" value="{{ $dateForInput }}">
                @foreach(request('Id_User', []) as $id)
                    <input type="hidden" name="Id_User[]" value="{{ $id }}">
                @endforeach
                <input type="hidden" name="statusFilter" value="{{ request('statusFilter') }}">
                <button class="btn btn-sm btn-outline-primary shadow-sm" type="submit">
                    <i class="fas fa-download fa-sm mr-1"></i>Download Report
                </button>
            </form>
        </div>
    </div>

    {{-- <form action="{{ route('admin_submission.reset') }}" method="POST" class="d-inline">
        @csrf
        <input type="hidden" name="Day_Request" value="{{ $date }}">
        <button class="btn btn-danger btn-md shadow-sm mb-4" type="submit" onclick="return confirm('Are you sure want to reset this submission data?')">
            <i class="fas fa-trash-alt"></i> Reset All Request
        </button>
    </form> --}}

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
                            <th>Sum Stock</th>
                            <th>Estimation Date</th>
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
                            <th>Sum Stock</th>
                            <th>Estimation Date</th>
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
                                    if ($s->Shipping_Request) {
                                        $label = $s->Ok_Stock == 1 ? 'OK Shipping' : 'Shipping';
                                        $statuses[] = '<span class="badge badge-info">' . $label . '</span>:' . $s->Shipping_Request;
                                    }
                                    if ($s->Production_Area_Request) {
                                        $statuses[] = '<span class="badge badge-primary">Production</span>:' . $s->Production_Area_Request;
                                    }
                                    if ($s->Design_Changes_Request) {
                                        $label = $s->Ok_Stock == 1 ? 'OK Design Change' : 'Design Change';
                                        $statuses[] = '<span class="badge badge-warning">' . $label . '</span>:' . $s->Design_Changes_Request;
                                    }
                                    echo implode(' | ', $statuses);
                                @endphp
                            </td>
                            <td>
                                @if($s->Shipping_Request || $s->Design_Changes_Request)
                                    {{ $s->Stock_Shipping ?? '' }}
                                @else
                                    {{ $s->Sum_Stock ?? '' }}
                                @endif
                            </td>
                            <td>{{ $s->Estimation_Stock ? \Carbon\Carbon::parse($s->Estimation_Stock)->format('d/m/Y') : '-' }}</td>
                            <td>{{ optional($s->record)->Day_Record ?? '' }} {{ optional($s->record)->Time_Record ?? '' }}</td>
                            <td>{{ optional($s->record)->Sum_Record ?? '' }}</td>
                            <td>{{ $s->display_name ?? '' }}</td>
                            <td>{{ optional($s->record)->display_name ?? '' }}</td>
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

function changeDate(offset) {
    var input = document.getElementById('Day_Request');
    var d = new Date(input.value + 'T00:00:00');
    d.setDate(d.getDate() + offset);
    input.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    document.getElementById('filterForm').submit();
}

function setToday() {
    var d = new Date();
    document.getElementById('Day_Request').value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    document.getElementById('filterForm').submit();
}
</script>
@endsection
