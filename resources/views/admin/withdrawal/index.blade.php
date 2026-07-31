@extends('layouts.main')

@section('style')
<link href="{{asset('vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
<style>
    .table-responsive {
        overflow-x: auto;
    }
    .table th {
        white-space: nowrap;
        text-align: center;
        vertical-align: middle;
        font-size: 0.85rem;
    }
    .table td {
        white-space: nowrap;
        text-align: center;
        vertical-align: middle;
        font-size: 0.85rem;
    }
    .th-pengajuan { background-color: #fff3cd !important; }
    .th-disiapkan { background-color: #d1ecf1 !important; }
    .th-diterima { background-color: #d4edda !important; }
    .th-selesai { background-color: #cce5ff !important; }
    .th-masuk-rak { background-color: #e2d5f1 !important; }
</style>
@endsection

@section('content')
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Withdrawal Data (Read-Only)</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th rowspan="2">No</th>
                            <th rowspan="2">Tgl</th>
                            <th colspan="2" class="th-pengajuan">Pengajuan QC</th>
                            <th colspan="2" class="th-disiapkan">Disiapkan Oleh</th>
                            <th class="th-diterima">Diterima</th>
                            <th class="th-selesai">Selesai</th>
                            <th colspan="3" class="th-masuk-rak">Masuk Rak</th>
                        </tr>
                        <tr>
                            <th class="th-pengajuan">Nama PIC</th>
                            <th class="th-pengajuan">Kode Part</th>
                            <th class="th-disiapkan">Nama / NIK</th>
                            <th class="th-disiapkan">Tgl & Jam</th>
                            <th class="th-diterima">Tgl & Jam</th>
                            <th class="th-selesai">Tgl & Jam</th>
                            <th class="th-masuk-rak">NIK / Nama</th>
                            <th class="th-masuk-rak">Kode Rak</th>
                            <th class="th-masuk-rak">Tgl & Jam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($withdrawals as $index => $w)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $w->Date_Withdrawal ? \Carbon\Carbon::parse($w->Date_Withdrawal)->format('d-m-Y H:i') : '-' }}</td>

                            {{-- Pengajuan QC --}}
                            <td>{{ $w->Name_Withdrawal ?? '-' }}</td>
                            <td>{{ $w->Code_Item_Withdrawal ?? '-' }}</td>

                            {{-- Disiapkan Oleh --}}
                            <td>
                                @if($w->Oke_Withdrawal)
                                    {{ $w->name_disiapkan ?? '-' }} / {{ $w->NIK_Withdrawal }}
                                @else
                                    <span class="badge badge-secondary">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($w->Oke_Withdrawal)
                                    {{ $w->Date_Oke_Withdrawal ? \Carbon\Carbon::parse($w->Date_Oke_Withdrawal)->format('d-m-Y H:i') : ($w->Date_Withdrawal ? \Carbon\Carbon::parse($w->Date_Withdrawal)->format('d-m-Y H:i') : '-') }}
                                @else
                                    -
                                @endif
                            </td>

                            {{-- Diterima --}}
                            <td>
                                @if($w->Oke_Receiving)
                                    {{ \Carbon\Carbon::parse($w->Date_Receiving)->format('d-m-Y H:i') }}
                                @else
                                    -
                                @endif
                            </td>

                            {{-- Selesai --}}
                            <td>
                                @if($w->Finish_Receiving)
                                    {{ \Carbon\Carbon::parse($w->Date_Finish_Receiving)->format('d-m-Y H:i') }}
                                @else
                                    -
                                @endif
                            </td>

                            {{-- Masuk Rak --}}
                            <td>
                                @if($w->Date_Return)
                                    {{ $w->name_return ?? '-' }} / {{ $w->NIK_Return }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $w->Code_Rack_Return ?? '-' }}</td>
                            <td>
                                @if($w->Date_Return)
                                    {{ \Carbon\Carbon::parse($w->Date_Return)->format('d-m-Y H:i') }}
                                @else
                                    -
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
@endsection

@section('script')
<script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>
@endsection
