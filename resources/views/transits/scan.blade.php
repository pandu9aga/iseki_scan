@extends('layouts.transit')
@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <div id="reader_item" class="mx-auto" style="max-width: 300px;"></div>
        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <div class="p-5">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <span class="badge bg-success">Success</span> {!! session('success') !!}
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
                            <form class="user text-center" action="{{ route('transit.scan.process') }}" id="recordForm"
                                method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 text-center">
                                        <div class="form-group mb-3">
                                            <div>
                                                <a href="#top">
                                                    <button type="button" id="scanItem" class="btn btn-warning btn-sm">
                                                        Scan Item Code
                                                    </button>
                                                </a>
                                            </div>
                                            <span style="font-size: small;">Item Code</span>
                                            <input type="text" name="Code_Item"
                                                onkeyup="this.value = this.value.toUpperCase();" id="Code_Item"
                                                class="form-control form-control-user @error('Code_Item') is-invalid @enderror"
                                                value="{{ old('Code_Item') }}" required>
                                            @error('Code_Item')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <span id="status_code" class="status"></span>
                                <hr>
                                <div class="row">
                                    <div class="col-lg-3 col-md-3 text-center"></div>
                                    <div class="col-lg-6 col-md-6 text-center">
                                        <button type="submit" id="saveBtn" class="btn btn-info btn-user"
                                            style="padding-left: 50px; padding-right: 50px;">
                                            Submit
                                        </button>
                                    </div>
                                    <div class="col-lg-3 col-md-3 text-center"></div>
                                </div>
                            </form>
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

        function onScanSuccessItem(decodedText, decodedResult) {
            // Bagi dengan '|', ambil index ke-0
            let parts = decodedText.split('|');
            let itemCode = parts[0];

            document.getElementById("Code_Item").value = itemCode;
            itemScanner.clear();
            checkCorrectness();
        }

        document.getElementById("scanItem").addEventListener("click", function () {
            itemScanner.render(onScanSuccessItem);
        });

        function checkCorrectness() {
            let itemValue = $("#Code_Item").val().trim();
            let statusCode = $("#status_code");

            // Hilangkan semua tanda baca dan spasi
            itemValue = itemValue.replace(/[^\w]/g, '');

            if (itemValue === "") {
                statusCode.html("").removeClass("bg-gradient-success bg-gradient-danger text-white");
                return;
            }

            // AJAX request ke server
            $.get("{{ route('transit.scan.check') }}", {
                Code_Item: itemValue
            }, function (response) {
                if (response.status === "correct") {
                    let d = response.details;
                    statusCode
                        .html(`
                                        <div style="font-size: 2rem;">✅</div>
                                        <div class='text-left' style='font-size: 0.9rem;'>
                                            <strong>Rack:</strong> ${d.rack_code}<br>
                                            <strong>Item:</strong> ${d.item_code}<br>
                                            <strong>Name:</strong> ${d.item_name}<br>
                                            <strong>Time:</strong> ${d.time_request}<br>
                                            <strong>Member:</strong> ${d.member_name}
                                        </div>
                                    `)
                        .removeClass("bg-gradient-danger")
                        .addClass("text-white bg-gradient-success p-3 rounded")
                        .css({
                            height: 'auto',
                            display: 'flex',
                            'flex-direction': 'column',
                            'align-items': 'center',
                            'justify-content': 'center',
                            'text-align': 'center'
                        });

                    statusCode[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    statusCode
                        .html(`
                                        <div style="font-size: 3rem;">❌</div>
                                        <div style="font-size: 1.8rem; font-weight: bold;">Not Found / Already Set</div>
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

                    statusCode[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }

        $("#Code_Item").on("blur", checkCorrectness);

        $("#recordForm").on("submit", function (e) {
            let codeItem = $("#Code_Item").val().trim();

            if (!codeItem) {
                e.preventDefault();
                alert("Isi dulu Item Code.");
            }
        });

    </script>
@endsection