@extends('layouts.main')

@section('style')
    <link href="{{ asset('css/select2.min.css') }}" rel="stylesheet">
    <style>
        .select2-container .select2-selection--single {
            height: 38px !important;
            line-height: 38px !important;
            border: 1px solid #d1d3e2 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px !important;
            color: #6e707e !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Add Mistake</h1>
            <a href="{{ route('mistake') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
            </a>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Record New Mistake</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('mistake.store') }}" method="POST" id="mistakeForm">
                            @csrf
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">Code Rack</label>
                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <input type="text" name="code_rack" id="code_rack" class="form-control"
                                            placeholder="Enter Rack Code" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button" id="btnSearchRack">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Press search or enter to fetch latest
                                        request.</small>
                                </div>
                                <label class="col-sm-2 col-form-label text-right">Date of Mistake</label>
                                <div class="col-sm-4">
                                    <input type="date" name="Day_Mistake" class="form-control" value="{{ date('Y-m-d') }}"
                                        required>
                                </div>
                            </div>

                            <hr>

                            <div id="requestDetails" style="display: none;">
                                <input type="hidden" name="Id_Request" id="Id_Request">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <table class="table table-sm table-bordered">
                                            <tr>
                                                <th width="40%" class="bg-light">Item Code</th>
                                                <td id="disp_Code_Item_Rack"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Item Name</th>
                                                <td id="disp_Name_Item"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Request Time</th>
                                                <td id="disp_Time_Request"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Member Request</th>
                                                <td id="disp_Member_Request"></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-sm table-bordered">
                                            <tr>
                                                <th width="40%" class="bg-light">Sum Request</th>
                                                <td id="disp_Sum_Request"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Status Ready</th>
                                                <td id="disp_Status_Ready"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Record Time</th>
                                                <td id="disp_Time_Record"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Member Record</th>
                                                <td id="disp_Member_Record"></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label font-weight-bold">PIC</label>
                                    <div class="col-sm-4">
                                        <select name="PIC" class="form-control select2" required>
                                            <option value="">-- Select PIC --</option>
                                            @foreach($pics as $pic)
                                                <option value="{{ $pic->nama }}">{{ $pic->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <label class="col-sm-2 col-form-label font-weight-bold">Category</label>
                                    <div class="col-sm-4">
                                        <select name="Category_Mistake" id="Category_Mistake" class="form-control" required>
                                            <option value="">-- Select Category --</option>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row" id="manualDetailRow" style="display: none;">
                                    <label class="col-sm-2 col-form-label font-weight-bold">Manual Detail</label>
                                    <div class="col-sm-10">
                                        <textarea name="Manual_Category_Detail" class="form-control" rows="2"
                                            placeholder="Specify other mistake details..."></textarea>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <button type="submit" class="btn btn-success px-5">Save Mistake</button>
                                </div>
                            </div>

                            <div id="noDataAlert" class="alert alert-warning text-center" style="display: none;">
                                No recent request found for this rack.
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('.select2').select2({
                theme: 'default',
                width: '100%',
                placeholder: '-- Select PIC --',
                allowClear: true
            });

            function fetchRequest() {
                var code = $('#code_rack').val();
                if (!code) return;

                $('#requestDetails').fadeOut();
                $('#noDataAlert').fadeOut();

                $.get("{{ route('mistake.get_latest_request') }}", { code_rack: code }, function (res) {
                    if (res.success) {
                        var d = res.data;
                        $('#Id_Request').val(d.Id_Request);
                        $('#disp_Code_Item_Rack').text(d.Code_Item_Rack);
                        $('#disp_Name_Item').text(d.Name_Item);
                        $('#disp_Time_Request').text(d.Time_Request);
                        $('#disp_Sum_Request').text(d.Sum_Request);
                        $('#disp_Status_Ready').text(d.Status_Ready || '-');
                        $('#disp_Member_Request').text(d.Member_Request);
                        $('#disp_Time_Record').text(d.Time_Record);
                        $('#disp_Member_Record').text(d.Member_Record);

                        $('#requestDetails').fadeIn();
                    } else {
                        $('#noDataAlert').text(res.message).fadeIn();
                    }
                });
            }

            $('#btnSearchRack').click(fetchRequest);
            $('#code_rack').keypress(function (e) {
                if (e.which == 13) {
                    e.preventDefault();
                    fetchRequest();
                }
            });

            $('#Category_Mistake').change(function () {
                if ($(this).val() == 'lain-lain') {
                    $('#manualDetailRow').slideDown();
                } else {
                    $('#manualDetailRow').slideUp();
                }
            });
        });
    </script>
@endsection