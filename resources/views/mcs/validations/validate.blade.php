@extends('layouts.mc')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800" id="top">Validate</h1>

    <div id="reader_item_rack" class="mx-auto mb-4" style="max-width: 300px;"></div>

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

                        <form class="user text-center" id="validationForm" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-12 text-center">
                                    <div class="form-group mb-3">
                                        <div>
                                            <a href="#top">
                                                <button type="button" id="scanItemRack" class="btn btn-warning btn-sm">
                                                    Scan
                                                </button>
                                            </a>
                                        </div>
                                        <span style="font-size: small;">Item Code</span>
                                        <input type="text" 
                                               name="Code_Item_Rack" 
                                               id="Code_Item_Rack"
                                               onkeyup="this.value = this.value.toUpperCase(); checkRackAuto()"
                                               class="form-control form-control-user @error('Code_Item_Rack') is-invalid @enderror"
                                               value="{{ old('Code_Item_Rack') }}"
                                               required>
                                        @error('Code_Item_Rack')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-12 text-center">
                                    <div class="form-group mb-3">
                                        <span style="font-size: small;">Rack Code</span>
                                        <input type="text" 
                                               name="Code_Rack" 
                                               id="Code_Rack"
                                               class="form-control form-control-user"
                                               readonly>
                                        <small id="rackStatus" class="form-text text-muted"></small>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="button" id="saveBtn" class="btn btn-info btn-user" 
                                            style="padding-left: 50px; padding-right: 50px;" disabled>
                                        Save
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pilih Request -->
    <div class="modal fade" id="requestModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Request untuk Validasi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm table-hover" style="font-size: 0.5rem;">
                            <thead>
                                <tr>
                                    <th>Day/Time</th>
                                    <th>Item-Rack</th>
                                    <th>Rack</th>
                                    <th>User</th>
                                    <th>Sum</th>
                                    <th>Area</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="requestOptions">
                                <!-- Diisi via JS -->
                            </tbody>
                        </table>
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

<script>
    let scanner = null;

    function startScanner() {
        if (scanner) {
            scanner.clear();
        }
        scanner = new Html5QrcodeScanner("reader_item_rack", {
            fps: 10,
            qrbox: { width: 150, height: 150 }
        });

        scanner.render(onScanSuccess, onScanFailure);
    }

    function onScanSuccess(decodedText) {
        const cleanText = decodedText.trim().toUpperCase();
        document.getElementById("Code_Item_Rack").value = cleanText;
        checkRackAuto();
        scanner.clear();
    }

    function onScanFailure(error) {
        // opsional
    }

    document.getElementById("scanItemRack").addEventListener("click", startScanner);

    // Cek rack otomatis saat input berubah (manual atau scan)
    function checkRackAuto() {
        const codeItemRack = document.getElementById("Code_Item_Rack").value.trim();
        const rackInput = document.getElementById("Code_Rack");
        const status = document.getElementById("rackStatus");
        const saveBtn = document.getElementById("saveBtn");

        rackInput.value = "";
        status.textContent = "";
        saveBtn.disabled = true;

        if (codeItemRack === "") return;

        status.textContent = "Checking rack...";

        $.ajax({
            url: "{{ route('mc.validate.check.rack') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                Code_Item_Rack: codeItemRack
            },
            success: function(response) {
                if (response.found) {
                    rackInput.value = response.Code_Rack;
                    status.textContent = "✅ Rack found";
                    status.className = "form-text text-success";
                    saveBtn.disabled = false;
                } else {
                    status.textContent = "❌ Rack not found";
                    status.className = "form-text text-danger";
                }
            },
            error: function() {
                status.textContent = "⚠️ Error checking rack";
                status.className = "form-text text-warning";
            }
        });
    }

    // Fungsi saat Save diklik
    document.getElementById("saveBtn").addEventListener("click", function() {
        const codeItemRack = document.getElementById("Code_Item_Rack").value.trim();
        const codeRack = document.getElementById("Code_Rack").value.trim();

        if (!codeItemRack || !codeRack) {
            alert("Lengkapi Code_Item_Rack dan Rack Code terlebih dahulu.");
            return;
        }

        // SELALU cek dulu ke /check-requests
        $.post("{{ route('mc.validate.check.requests') }}", {
            _token: "{{ csrf_token() }}",
            Code_Item_Rack: codeItemRack,
            Code_Rack: codeRack
        }, function(response) {
            if (response.status === 'none') {
                // Tidak ada request → langsung simpan dengan Id_Request = null
                submitValidation(null);
            } else if (response.status === 'single') {
                // Ada 1 → simpan dengan Id_Request
                submitValidation(response.Id_Request);
            } else if (response.status === 'multiple') {
                // Fungsi bantu: tampilkan nilai, ganti null/undefined jadi string kosong
                function displayValue(val) {
                    return (val === null || val === undefined || val === 'null') ? '' : val;
                }

                // Tampilkan modal
                const tbody = document.getElementById("requestOptions");
                tbody.innerHTML = "";
                response.requests.forEach(r => {
                    const row = `
                        <tr>
                            <td>${displayValue(r.Day_Request)} ${displayValue(r.Time_Request)}</td>
                            <td>${displayValue(r.Code_Item_Rack)}</td>
                            <td>${displayValue(r.Code_Rack)}</td>
                            <td>${displayValue(r.Name_Member)}</td>
                            <td>${displayValue(r.Sum_Request)}</td>
                            <td>${displayValue(r.Area_Request)}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" onclick="selectRequest(${r.Id_Request})">
                                    Pilih
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });

                // 👇 BARIS INI YANG KURANG!
                $("#requestModal").modal("show");
            }
        }).fail(function(xhr) {
            alert("Error: " + (xhr.responseJSON?.message || "Failed to check requests"));
        });
    });

    // Fungsi pilih request dari modal
    function selectRequest(idRequest) {
        $("#requestModal").modal("hide");
        submitValidation(idRequest);
    }

    // Fungsi submit akhir
    function submitValidation(idRequest) {
        const formData = new FormData();
        formData.append('_token', "{{ csrf_token() }}");
        formData.append('Code_Item_Rack', document.getElementById("Code_Item_Rack").value);
        formData.append('Code_Rack', document.getElementById("Code_Rack").value);
        formData.append('Id_Request', idRequest);

        fetch("{{ route('mc.validate.store') }}", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Validasi berhasil disimpan!");
                // Reset form
                document.getElementById("Code_Item_Rack").value = "";
                document.getElementById("Code_Rack").value = "";
                document.getElementById("rackStatus").textContent = "";
                document.getElementById("saveBtn").disabled = true;
            } else {
                alert("Gagal: " + (data.message || "Error tidak diketahui"));
            }
        })
        .catch(err => {
            console.error(err);
            alert("Terjadi kesalahan jaringan!");
        });
    }
</script>
@endsection