@extends('layouts.main')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800" id="top">Recording</h1>

    <div id="reader_item" class="mx-auto" style="max-width: 300px;"></div>
    <div id="reader_rack" class="mx-auto" style="max-width: 300px;"></div>
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col">
                    <div class="p-5">
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
                        <form class="user text-center" action="{{ route('admin.recording.create') }}" id="recordForm"
                            method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6 col-md-6 text-center">
                                    <div class="form-group mb-3">
                                        <div>
                                            <a href="#top">
                                                <button type="button" id="scanItem" class="btn btn-warning btn-sm">
                                                    Scan
                                                </button>
                                            </a>
                                        </div>
                                        <span style="font-size: small;">Item Code</span>
                                        <input type="text" name="Code_Item"
                                            onkeyup="this.value = this.value.toUpperCase();" id="Code_Item"
                                            class="form-control form-control-user @error('Code_Item') is-invalid @enderror"
                                            value="{{ old('Code_Item') }}" required readonly>
                                        @error('Code_Item')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 text-center">
                                    <div class="form-group mb-3">
                                        <div>
                                            <a href="#top">
                                                <button type="button" id="scanRack" class="btn btn-warning btn-sm">
                                                    Scan
                                                </button>
                                            </a>
                                        </div>
                                        <span style="font-size: small;">Rack Code</span>
                                        <input type="text" name="Code_Rack"
                                            onkeyup="this.value = this.value.toUpperCase();" id="Code_Rack"
                                            class="form-control form-control-user @error('Code_Rack') is-invalid @enderror"
                                            value="{{ old('Code_Rack') }}" required readonly>
                                        @error('Code_Rack')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 text-center">
                                    <div class="form-group mb-3">
                                        <label for="Sum_Record" style="font-size: small;">Sum Record</label>
                                        <input type="number" name="Sum_Record" id="Sum_Record"
                                            class="form-control form-control-user @error('Sum_Record') is-invalid @enderror"
                                            value="{{ old('Sum_Record', 1) }}" min="1" step="1" required>
                                        @error('Sum_Record')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <input type="hidden" id="Correctness" name="Correctness" value="">
                            </div>
                            <hr>
                            <span id="status_code" class="status"></span>
                            <hr>
                            <div class="row">
                                <div class="col-lg-3 col-md-3 text-center"></div>
                                <div class="col-lg-6 col-md-6 text-center">
                                    <button type="button" id="saveBtn" class="btn btn-info btn-user"
                                        style="padding-left: 50px; padding-right: 50px;">
                                        Save
                                    </button>
                                </div>
                                <div class="col-lg-3 col-md-3 text-center"></div>
                            </div>
                        </form>
                        <!-- Modal Pilih Area -->
                        <div class="modal fade" id="areaModal" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Pilih Area Supply</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div id="areaOptions" class="list-group"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Modal Error -->
                        <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title">Kode Part Salah</h5>
                                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Tidak ada request dengan kode part tersebut</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<!-- QR Code Library -->
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/qrcode.min.js') }}"></script>

<!-- QR Code Generation Script -->
<script>
    var width = 150;

    let itemScanner = new Html5QrcodeScanner(
        "reader_item", {
            fps: 10,
            qrbox: {
                width: width,
                height: width,
            },
        }
    );

    let rackScanner = new Html5QrcodeScanner(
        "reader_rack", {
            fps: 10,
            qrbox: {
                width: width,
                height: width,
            },
        }
    );

    function onScanSuccessItem(decodedText, decodedResult) {
        // Bagi dengan '|', ambil index ke-0
        let parts = decodedText.split('|');
        let itemCode = parts[0];

        document.getElementById("Code_Item").value = itemCode;
        itemScanner.clear();
        checkCorrectness();
    }

    document.getElementById("scanItem").addEventListener("click", function() {
        itemScanner.render(onScanSuccessItem);
    });

    function onScanSuccessRack(decodedText, decodedResult) {
        document.getElementById("Code_Rack").value = decodedText;
        rackScanner.clear();
        checkCorrectness();
    }

    document.getElementById("scanRack").addEventListener("click", function() {
        rackScanner.render(onScanSuccessRack);
    });

    function checkCorrectness() {
        let itemValue = $("#Code_Item").val().trim();
        let rackValue = $("#Code_Rack").val().trim();
        let statusCode = $("#status_code");

        // Hilangkan semua tanda baca dan spasi
        itemValue = itemValue.replace(/[^\w]/g, '');

        if (itemValue === "" || rackValue === "") {
            statusCode.html("").removeClass("bg-gradient-success bg-gradient-danger text-white");
            return;
        }

        // AJAX request ke server
        $.get('{{ route("admin.recording.check") }}', {
            Code_Rack: rackValue,
            Code_Item: itemValue
        }, function(response) {
            if (response.status === "correct") {
                statusCode
                    .html(`
                            <div style="font-size: 3rem;">✅</div>
                            <div style="font-size: 1.8rem; font-weight: bold;">Correct!</div>
                            <div style="font-size: 2rem;">😊</div>
                        `)
                    .removeClass("bg-gradient-danger")
                    .addClass("text-white bg-gradient-success p-3 rounded")
                    .css({
                        height: '180px',
                        display: 'flex',
                        'flex-direction': 'column',
                        'align-items': 'center',
                        'justify-content': 'center',
                        'text-align': 'center'
                    });

                statusCode[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                document.getElementById("Correctness").value = 1;
            } else {
                statusCode
                    .html(`
                            <div style="font-size: 3rem;">❌</div>
                            <div style="font-size: 1.8rem; font-weight: bold;">Incorrect!</div>
                            <div style="font-size: 2rem;">😢</div>
                        `)
                    .removeClass("bg-gradient-success")
                    .addClass("text-white bg-gradient-danger p-3 rounded")
                    .css({
                        height: '180px',
                        display: 'flex',
                        'flex-direction': 'column',
                        'align-items': 'center',
                        'justify-content': 'center',
                        'text-align': 'center'
                    });

                statusCode[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                document.getElementById("Correctness").value = 2;
            }

        });
    }

    $("#Code_Item, #Code_Rack").on("blur", checkCorrectness);

    $("#saveBtn").on("click", function() {
        let form = $("#recordForm");
        let codeItem = $("#Code_Item").val();
        let codeRack = $("#Code_Rack").val();

        if (!codeItem || !codeRack) {
            alert("Isi dulu Item Code dan Rack Code.");
            return;
        }

        $.post("{{ route('admin.recording.checkMultiple') }}", {
            _token: "{{ csrf_token() }}",
            Code_Item: codeItem,
            Code_Rack: codeRack
        }, function(response) {
            if (response.count === 0) {
                // tidak ada request matching -> tampilkan modal error
                $("#errorModal").modal("show");
            } else if (response.count === 1) {
                // hanya 1 -> langsung submit
                form.append(`<input type="hidden" name="Id_Request" value="${response.requests[0].id}">`);
                form.submit();
            } else {
                // lebih dari 1 -> tampilkan modal
                let areaOptions = $("#areaOptions");
                areaOptions.empty();
                response.requests.forEach(r => {
                    areaOptions.append(`
                            <button type="button" class="list-group-item list-group-item-action"
                                onclick="selectArea('${r.id}')">
                                ${r.area}
                            </button>
                        `);
                });
                $("#areaModal").modal("show");
            }
        });
    });

    function selectArea(idRequest) {
        $("#recordForm").append(`<input type="hidden" name="Id_Request" value="${idRequest}">`);
        $("#areaModal").modal("hide");
        $("#recordForm").submit();
    }
</script>
@endsection

@section('style')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection