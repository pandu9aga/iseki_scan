@extends('layouts.user')
@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <h1 class="h3 mb-2 text-gray-800">Record</h1>
        <!-- Page Heading -->
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
                                <form class="user" action="{{ route('user_report.submit') }}" method="GET">
                                    @csrf
                                    <div class="row d-flex align-items-center">
                                        <div class="col-lg-8 col-md-6 mb-1">
                                            <input name="Day_Record" type="date" class="form-control" value="{{ $date }}">
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
            <form class="user" action="{{ route('user_report.export') }}" method="GET" target="_blank">
                <input name="Day_Record_Hidden" type="hidden" class="form-control form-control-user" value="{{ $date }}">
                <button class="d-sm-inline-block btn btn-md btn-primary shadow-sm" type="submit">
                    <i class="fas fa-download fa-sm text-white-50"></i> Download Record
                </button>
            </form>
        </div>

        <a href="{{ route('record') }}">
            <button class="btn btn-lg btn-primary shadow-sm ms-auto mb-4" type="button">
                <i class="fas fa-qrcode fa-sm text-white-50"></i> Record
            </button>
        </a>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <div class="row d-flex">
                    <h6 class="m-0 font-weight-bold text-primary col-md-8">Record: {{ $formattedDate }}</h6>
                    <h6 class="m-0 font-weight-bold text-success col-md-2">Correct: {{ $correct }}</h6>
                    <h6 class="m-0 font-weight-bold text-danger col-md-2">Incorrect: {{ $incorrect }}</h6>
                </div>
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
                                {{-- <th>Action</th> --}}
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
                                {{-- <th>Action</th> --}}
                            </tr>
                        </tfoot>
                        <tbody>
                            @foreach ($records as $r)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $r->Day_Record }} {{ $r->Time_Record }}</td>
                                    <td>{{ $r->request->Area_Request ?? '' }}</td>
                                    <td>{{ $r->Code_Rack }}</td>
                                    <td title="{{ $r->rack->Type_Tractor_Rack ?? '-' }}">
                                        {{ \Illuminate\Support\Str::limit($r->rack->Type_Tractor_Rack ?? '-', 20) }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span>{{ $r->Sum_Record }}</span>
                                            <button class="btn btn-sm btn-link text-warning p-0 ml-2" onclick="openEditSum(
                                                {{ $r->Id_Record }},
                                                {{ $r->Sum_Record }},
                                                '{{ $r->Code_Rack ?? '' }}',
                                                '{{ $r->Code_Item_Rack ?? '' }}',
                                                {{ $r->Id_Request ?? 0 }}
                                            )" title="Edit Sum Record">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
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
                                    <td>{{ optional($r->request)->Day_Request ?? '' }}
                                        {{ optional($r->request)->Time_Request ?? '' }}</td>
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
    {{--
    <script src="{{asset('js/demo/datatables-demo.js')}}"></script> --}}

    {{-- Modal Edit Sum Record --}}
    <div class="modal fade" id="editSumModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i>Edit Sum Record</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-2 small text-muted" id="editSumInfo"></div>
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Sum Record Baru</label>
                        <input type="number" id="editSumInput" class="form-control" min="1" step="1">
                    </div>
                    <div id="editSumAlert" class="alert alert-warning small py-1 px-2 mt-2" style="display:none;"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning btn-sm" id="editSumSaveBtn">
                        <i class="fas fa-save mr-1"></i>Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        var _editRecordId = null;

        function openEditSum(recordId, currentSum, codeRack, codeItem, idRequest) {
            _editRecordId = recordId;
            var info = 'Rack: <strong>' + codeRack + '</strong> &nbsp;|&nbsp; Item: <strong>' + codeItem + '</strong>';
            if (idRequest && idRequest != 0) {
                info += '<br><span class="text-info">Terkait request #' + idRequest + ' — perubahan akan mempengaruhi Part Sum Not Match</span>';
            } else {
                info += '<br><span class="text-muted">Tidak terkait request, hanya update angka.</span>';
            }
            document.getElementById('editSumInfo').innerHTML = info;
            document.getElementById('editSumInput').value = currentSum;
            document.getElementById('editSumAlert').style.display = 'none';
            $('#editSumModal').modal('show');
            setTimeout(function() { document.getElementById('editSumInput').focus(); }, 400);
        }

        document.getElementById('editSumSaveBtn').addEventListener('click', function() {
            var newSum = parseInt(document.getElementById('editSumInput').value);
            if (!newSum || newSum < 1) {
                document.getElementById('editSumAlert').textContent = 'Sum harus minimal 1.';
                document.getElementById('editSumAlert').style.display = 'block';
                return;
            }
            var btn = document.getElementById('editSumSaveBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...';

            $.ajax({
                url: '{{ url("/record") }}/' + _editRecordId + '/update-sum',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    Sum_Record: newSum
                },
                success: function(res) {
                    $('#editSumModal').modal('hide');
                    // Reload halaman agar tabel ikut terupdate (termasuk correctness dsb)
                    location.reload();
                },
                error: function(xhr) {
                    var msg = 'Gagal menyimpan.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    document.getElementById('editSumAlert').textContent = msg;
                    document.getElementById('editSumAlert').style.display = 'block';
                    
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save mr-1"></i>Simpan';
                }
            });
        });
    </script>
@endsection