@extends('layouts.user')

@section('style')
<link href="{{asset('vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
<style>
    .table th, .table td {
        white-space: nowrap;
        text-align: center;
        vertical-align: middle;
        font-size: 0.85rem;
    }
    .card-withdrawal {
        border-left: 4px solid;
        margin-bottom: 1rem;
    }
    .card-pending { border-left-color: #f6c23e; }
    .card-return { border-left-color: #4e73df; }
    .card-history { border-left-color: #1cc88a; }
    .btn-action {
        font-size: 0.8rem;
        padding: 0.3rem 0.8rem;
    }
    .scan-area {
        margin-top: 10px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">
        <i class="fas fa-exchange-alt"></i> Withdrawal
    </h1>

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

    {{-- Tab Navigation --}}
    <ul class="nav nav-tabs mb-3" id="withdrawalTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab">
                <i class="fas fa-clock"></i> Menunggu Disiapkan
                @if($pendingOke->count() > 0)
                    <span class="badge badge-warning">{{ $pendingOke->count() }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="return-tab" data-toggle="tab" href="#return" role="tab">
                <i class="fas fa-undo"></i> Masuk Rak
                @if($pendingReturn->count() > 0)
                    <span class="badge badge-primary">{{ $pendingReturn->count() }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="history-tab" data-toggle="tab" href="#history" role="tab">
                <i class="fas fa-history"></i> Riwayat
            </a>
        </li>
    </ul>

    <div class="tab-content" id="withdrawalTabContent">

        {{-- Tab: Menunggu Disiapkan (Pending OK) --}}
        <div class="tab-pane fade show active" id="pending" role="tabpanel">
            @if($pendingOke->count() == 0)
                <div class="alert alert-info">Tidak ada permintaan withdrawal yang menunggu.</div>
            @else
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%">
                                <thead class="thead-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Nama PIC (QC)</th>
                                        <th>Kode Part</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingOke as $index => $w)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $w->Date_Withdrawal ? \Carbon\Carbon::parse($w->Date_Withdrawal)->format('d-m-Y H:i') : '-' }}</td>
                                        <td>{{ $w->Name_Withdrawal }}</td>
                                        <td><strong>{{ $w->Code_Item_Withdrawal }}</strong></td>
                                        <td>
                                            <form action="{{ route('user.withdrawal.oke', $w->Id_Withdrawal) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-warning btn-action" onclick="return confirm('Konfirmasi Anda akan menyiapkan barang ini?')">
                                                    <i class="fas fa-check"></i> OK Siapkan
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Tab: Masuk Rak (Pending Return) --}}
        <div class="tab-pane fade" id="return" role="tabpanel">
            @if($pendingReturn->count() == 0)
                <div class="alert alert-info">Tidak ada barang yang perlu dikembalikan ke rak.</div>
            @else
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%">
                                <thead class="thead-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal Pengajuan</th>
                                        <th>Nama PIC (QC)</th>
                                        <th>Kode Part</th>
                                        <th>Selesai QC</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingReturn as $index => $w)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $w->Date_Withdrawal ? \Carbon\Carbon::parse($w->Date_Withdrawal)->format('d-m-Y H:i') : '-' }}</td>
                                        <td>{{ $w->Name_Withdrawal }}</td>
                                        <td><strong>{{ $w->Code_Item_Withdrawal }}</strong></td>
                                        <td>{{ $w->Date_Finish_Receiving ? \Carbon\Carbon::parse($w->Date_Finish_Receiving)->format('d-m-Y H:i') : '-' }}</td>
                                        <td>
                                            <button class="btn btn-primary btn-action" data-toggle="modal" data-target="#modalReturn{{ $w->Id_Withdrawal }}">
                                                <i class="fas fa-undo"></i> Masuk Rak
                                            </button>
                                        </td>
                                    </tr>

                                    {{-- Modal Return --}}
                                    <div class="modal fade" id="modalReturn{{ $w->Id_Withdrawal }}" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <form action="{{ route('user.withdrawal.return', $w->Id_Withdrawal) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Masuk Rak - {{ $w->Code_Item_Withdrawal }}</h5>
                                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-info mb-3">
                                                            <small>
                                                                <i class="fas fa-user"></i> NIK: <strong>{{ session('NIK_Member') }}</strong> ({{ session('Name_Member') }})<br>
                                                                <i class="fas fa-tag"></i> Kode Part: <strong>{{ $w->Code_Item_Withdrawal }}</strong>
                                                            </small>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Scan Barcode Rak</label>
                                                            <input type="text" name="Code_Rack_Return" id="codeRack{{ $w->Id_Withdrawal }}" class="form-control" placeholder="Scan barcode rak" required autofocus>
                                                            <small class="text-muted">Barcode rak harus sesuai dengan kode part: <strong>{{ $w->Code_Item_Withdrawal }}</strong></small>
                                                        </div>
                                                        <div id="readerRack{{ $w->Id_Withdrawal }}" class="scan-area"></div>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2 btnScan" data-id="{{ $w->Id_Withdrawal }}">
                                                            <i class="fas fa-qrcode"></i> Scan QR Code
                                                        </button>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-save"></i> Simpan
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Tab: Riwayat --}}
        <div class="tab-pane fade" id="history" role="tabpanel">
            @if($history->count() == 0)
                <div class="alert alert-info">Belum ada riwayat withdrawal.</div>
            @else
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm" id="dataTable" width="100%">
                                <thead class="thead-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>PIC QC</th>
                                        <th>Kode Part</th>
                                        <th>Disiapkan</th>
                                        <th>Diterima</th>
                                        <th>Selesai</th>
                                        <th>Masuk Rak</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($history as $index => $w)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $w->Date_Withdrawal ? \Carbon\Carbon::parse($w->Date_Withdrawal)->format('d-m-Y H:i') : '-' }}</td>
                                        <td>{{ $w->Name_Withdrawal }}</td>
                                        <td>{{ $w->Code_Item_Withdrawal }}</td>
                                        <td>{{ $w->name_disiapkan ?? '-' }}</td>
                                        <td>{{ $w->Date_Receiving ? \Carbon\Carbon::parse($w->Date_Receiving)->format('d-m-Y H:i') : '-' }}</td>
                                        <td>{{ $w->Date_Finish_Receiving ? \Carbon\Carbon::parse($w->Date_Finish_Receiving)->format('d-m-Y H:i') : '-' }}</td>
                                        <td>{{ $w->Date_Return ? \Carbon\Carbon::parse($w->Date_Return)->format('d-m-Y H:i') : '-' }}</td>
                                        <td>
                                            @if($w->Date_Return)
                                                <span class="badge badge-success">Selesai</span>
                                            @elseif($w->Finish_Receiving)
                                                <span class="badge badge-info">Menunggu Return</span>
                                            @elseif($w->Oke_Receiving)
                                                <span class="badge badge-primary">QC Proses</span>
                                            @elseif($w->Oke_Withdrawal)
                                                <span class="badge badge-warning">Disiapkan</span>
                                            @else
                                                <span class="badge badge-secondary">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
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
    var scanners = {};

    // QR scan for rack barcode
    $('.btnScan').on('click', function() {
        var id = $(this).data('id');
        var readerId = 'readerRack' + id;

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
            $('#codeRack' + id).val(decodedText.trim());
            scanners[id].clear();
            delete scanners[id];
        });
    });
});
</script>
@endsection
