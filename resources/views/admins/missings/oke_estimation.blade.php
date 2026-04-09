@extends('layouts.main')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Oke Estimation</h1>

    <div class="mb-4 col-md-4 col-lg-3">
        <form action="{{ route('admin.oke.estimation.export') }}" method="GET" target="_blank" class="mr-2">
            <input name="Day_Request_Hidden" type="hidden" value="{{ $date }}">
            <button class="d-sm-inline-block btn btn-md btn-primary shadow-sm" type="submit">
                <i class="fas fa-download fa-sm text-white-50"></i> Download Oke Estimation
            </button>
        </form>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Estimation yang Sudah OK ({{ $totalRequests }} items)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead class="text-primary">
                        <tr>
                            <th>No</th>
                            <th>Rack</th>
                            <th>Item</th>
                            <th>Name</th>
                            <th>Sum</th>
                            <th>Status OK</th>
                            <th>Time Request</th>
                            <th>Estimation Date</th>
                            <th>Stock Shipping</th>
                            <th>Time OK</th>
                            <th>PIC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $s)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $s->Code_Rack }}</td>
                            <td>{{ $s->Code_Item_Rack }}</td>
                            <td>{{ $s->rack->Name_Item_Rack ?? '' }}</td>
                            <td>{{ $s->Sum_Request }}</td>
                            <td class="text-center">
                                @if($s->Design_Changes_Request)
                                <span class="badge badge-warning">Oke Perubahan Design</span>
                                @elseif($s->Shipping_Request)
                                <span class="badge badge-info">Oke Shipping</span>
                                @endif
                            </td>
                            <td>{{ $s->Day_Request }} {{ $s->Time_Request }}</td>
                            <td class="text-center">
                                @if($s->Estimation_Stock)
                                {{ \Carbon\Carbon::parse($s->Estimation_Stock)->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="text-center">{{ $s->Stock_Shipping ?? '-' }}</td>
                            <td>
                                @if($s->Time_Ok_Stock)
                                {{ \Carbon\Carbon::parse($s->Time_Ok_Stock)->format('d/m/Y H:i') }}
                                @endif
                            </td>
                            <td>{{ $s->member->Name_Member ?? '' }}</td>
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
