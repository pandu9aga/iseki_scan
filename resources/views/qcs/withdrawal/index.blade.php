@extends('layouts.qc')

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
    .th-pengajuan {
        background-color: #fff3cd !important;
    }
    .th-disiapkan {
        background-color: #d1ecf1 !important;
    }
    .th-diterima {
        background-color: #d4edda !important;
    }
    .th-selesai {
        background-color: #cce5ff !important;
    }
    .th-masuk-rak {
        background-color: #e2d5f1 !important;
    }
    .btn-action {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    .badge-step {
        font-size: 0.75rem;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Withdrawal QC</h1>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalPengajuan">
            <i class="fas fa-plus fa-sm"></i> Pengajuan Baru
        </button>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Withdrawal Table --}}
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
                                    <button class="btn btn-warning btn-action" data-toggle="modal" data-target="#modalOke{{ $w->Id_Withdrawal }}">
                                        <i class="fas fa-check"></i> OK
                                    </button>
                                @endif
                            </td>
                            <td>
                                @if($w->Oke_Withdrawal)
                                    {{ $w->Date_Withdrawal ? \Carbon\Carbon::parse($w->Date_Withdrawal)->format('d-m-Y H:i') : '-' }}
                                @else
                                    -
                                @endif
                            </td>

                            {{-- Diterima --}}
                            <td>
                                @if($w->Oke_Receiving)
                                    {{ \Carbon\Carbon::parse($w->Date_Receiving)->format('d-m-Y H:i') }}
                                @elseif($w->Oke_Withdrawal)
                                    <form action="{{ route('qc.withdrawal.receiving', $w->Id_Withdrawal) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-action" onclick="return confirm('Konfirmasi barang diterima?')">
                                            <i class="fas fa-check"></i> Diterima
                                        </button>
                                    </form>
                                @else
                                    -
                                @endif
                            </td>

                            {{-- Selesai --}}
                            <td>
                                @if($w->Finish_Receiving)
                                    {{ \Carbon\Carbon::parse($w->Date_Finish_Receiving)->format('d-m-Y H:i') }}
                                @elseif($w->Oke_Receiving)
                                    <form action="{{ route('qc.withdrawal.finish', $w->Id_Withdrawal) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-info btn-action" onclick="return confirm('Konfirmasi QC selesai?')">
                                            <i class="fas fa-check"></i> Selesai
                                        </button>
                                    </form>
                                @else
                                    -
                                @endif
                            </td>

                            {{-- Masuk Rak --}}
                            <td>
                                @if($w->Date_Return)
                                    {{ $w->name_return ?? '-' }} / {{ $w->NIK_Return }}
                                @elseif($w->Finish_Receiving)
                                    <button class="btn btn-primary btn-action" data-toggle="modal" data-target="#modalReturn{{ $w->Id_Withdrawal }}">
                                        <i class="fas fa-undo"></i> Masuk Rak
                                    </button>
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

                        {{-- Modal OK (Disiapkan) --}}
                        @if(!$w->Oke_Withdrawal)
                        <div class="modal fade" id="modalOke{{ $w->Id_Withdrawal }}" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('qc.withdrawal.oke', $w->Id_Withdrawal) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Disiapkan Oleh - {{ $w->Code_Item_Withdrawal }}</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>NIK Member DST</label>
                                                <input type="number" name="NIK_Withdrawal" class="form-control" placeholder="Masukkan NIK" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-warning">OK Siapkan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Modal Masuk Rak (Return) --}}
                        @if($w->Finish_Receiving && !$w->Date_Return)
                        <div class="modal fade" id="modalReturn{{ $w->Id_Withdrawal }}" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('qc.withdrawal.return', $w->Id_Withdrawal) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Masuk Rak - {{ $w->Code_Item_Withdrawal }}</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>NIK Member DST</label>
                                                <input type="number" name="NIK_Return" class="form-control" placeholder="Masukkan NIK" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Scan Barcode Rak</label>
                                                <input type="text" name="Code_Rack_Return" id="codeRackReturn{{ $w->Id_Withdrawal }}" class="form-control" placeholder="Scan barcode rak" required>
                                                <small class="text-muted">Kode rak harus sesuai dengan kode part: <strong>{{ $w->Code_Item_Withdrawal }}</strong></small>
                                            </div>
                                            <div id="readerReturn{{ $w->Id_Withdrawal }}" style="width:100%; margin-top:10px;"></div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm mt-2 btnScanReturn" data-id="{{ $w->Id_Withdrawal }}">
                                                <i class="fas fa-qrcode"></i> Scan QR
                                            </button>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif

                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Pengajuan Baru --}}
<div class="modal fade" id="modalPengajuan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('qc.withdrawal.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Pengajuan Withdrawal Baru</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama PIC</label>
                        <input type="text" name="Name_Withdrawal" class="form-control" placeholder="Masukkan nama PIC" value="{{ old('Name_Withdrawal') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Kode Part</label>
                        <input type="text" name="Code_Item_Withdrawal" class="form-control" placeholder="Masukkan kode part" value="{{ old('Code_Item_Withdrawal') }}" required>
                        <small class="text-muted">Kode part harus terdaftar di data rak.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script>
$(document).ready(function() {
    // QR scan for return barcode
    var scanners = {};

    $('.btnScanReturn').on('click', function() {
        var id = $(this).data('id');
        var readerId = 'readerReturn' + id;

        if (scanners[id]) {
            scanners[id].clear();
            delete scanners[id];
            return;
        }

        scanners[id] = new Html5QrcodeScanner(readerId, {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        });

        scanners[id].render(function(decodedText) {
            $('#codeRackReturn' + id).val(decodedText.trim());
            scanners[id].clear();
            delete scanners[id];
        });
    });
});
</script>
@endsection
