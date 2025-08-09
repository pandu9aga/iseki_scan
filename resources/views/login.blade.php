<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Iseki</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('img/logo-iseki.png') }}" />
    <link href="{{ asset('bootstrap/css/bootstrap.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('style/style.css') }}" />
    <style>
        body {
            color: white;
        }

        .text-black {
            color: #212529 !important;
        }

        .bg-primary {
            background-color: #df4e97 !important;
        }

        .btn-primary {
            background-color: #df4e97;
            border-color: #be229a;
        }

        .btn-primary:hover {
            background-color: #d92ea6;
            border-color: #d92ea1;
        }

        .nav-pills .nav-link.active {
            background-color: #df4e97 !important;
            color: white !important;
        }

        .nav-pills .nav-link {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .form-label,
        h5,
        h1,
        h2,
        p {
            color: white !important;
        }

        .form-control {
            background-color: #fff;
            color: #000;
        }

        .tab-content {
            background-color: rgba(0, 0, 0, 0.5);
            padding: 20px;
            border-radius: 10px;
        }

        .card-shadow {
            background-color: #fff;
        }

        .alert-danger {
            background-color: #d9534f;
            color: white;
            border: none;


        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary" id="login">
        <div class="container">
            <a class="navbar-brand" href="{{ route('login') }}"><b>ISEKI</b></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar"
                aria-controls="collapsibleNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="collapsibleNavbar">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item px-2">
                        <a class="nav-link" href="#login">Login</a>
                    </li>
                    <li class="nav-item px-2">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid bg-image" style="background-image: url('{{ asset('img/bg.png') }}'); min-height: 80vh;">
        <div class="row h-100 align-items-center">
            <div class="col-md-4 offset-md-1 text-white">
                <h1>Iseki Indonesia</h1>
                <h2>Scanner Code</h2>
                <p>Website untuk monitoring rak dan item part.</p>
            </div>

            <div class="col-md-6">
                <!-- Nav pills -->
                <ul class="nav nav-pills nav-fill bg-dark rounded mb-3" id="loginTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="member-tab" data-bs-toggle="pill"
                            data-bs-target="#member" type="button" role="tab" aria-controls="member"
                            aria-selected="true">Member</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="admin-tab" data-bs-toggle="pill" data-bs-target="#admin"
                            type="button" role="tab" aria-controls="admin" aria-selected="false">Admin</button>
                    </li>
                </ul>

                <!-- Tab content -->
                <div class="tab-content">
                    <!-- Member login form -->
                    <div class="tab-pane fade show active" id="member" role="tabpanel" aria-labelledby="member-tab">
                        <form id="memberLoginForm" action="{{ route('login.member') }}" method="POST" class="text-start px-2">
                            @csrf
                            <h5 class="mb-3">Login Member</h5>

                            <div class="mb-3">
                                <label for="NIK_Member" class="form-label">NIK</label>
                                <input type="text" id="NIK_Input" name="NIK_Member" class="form-control" required />
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary w-100">Login</button>
                            </div>
                            <br>
                            <div>
                                <button id="scanNIK" class="btn btn-primary w-100">Scan</button>
                            </div>
                            <div id="reader_nik" style="width: 100%; margin-top: 20px;"></div>
                        </form>

                    </div>
                    <!-- Admin login form -->
                    <div class="tab-pane fade" id="admin" role="tabpanel" aria-labelledby="admin-tab">
                        <form action="{{ route('login.auth') }}" method="POST" class="text-start px-2">
                            @csrf
                            <h5 class="mb-3">Login Admin</h5>

                            <div class="mb-3">
                                <label for="Username_User" class="form-label">Username</label>
                                <input type="text" id="Username_User" name="Username_User" class="form-control"
                                    required />
                            </div>

                            <div class="mb-3">
                                <label for="Password_User" class="form-label">Password</label>
                                <input type="password" id="Password_User" name="Password_User" class="form-control"
                                    required />
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>

                <!-- Error display -->
                @if ($errors->any())
                    <div class="mt-3 alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <br />

    <div class="container-fluid py-4">
        <div class="row align-items-center text-center pt-4 mb-2" id="about">
            <h3 class="text-black"><b>Iseki</b> Scanner</h3>
            <p class="text-black">Merupakan website yang digunakan oleh PT. Iseki Indonesia dalam melakukan monitoring
                rak dan item part
                dengan melakukan scan pada item code.</p>
        </div>

        <div class="row px-4 mb-4">
            <div class="col-sm-4 mb-4">
                <div class="card card-shadow text-center text-black border-light">
                    <div class="card-body">
                        <h4>Use</h4>
                        <p class="text-black">Gunakan part yang ada di rak.</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 mb-4">
                <div class="card card-shadow text-center text-black border-light">
                    <div class="card-body">
                        <h4>Put</h4>
                        <p class="text-black">Letakkan part sesuai dengan tempatnya.</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 mb-4">
                <div class="card card-shadow text-center text-black border-light">
                    <div class="card-body">
                        <h4>Scan</h4>
                        <p class="text-black">Scan kode item dan rak untuk memonitoring part.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br />

    <footer class="bg-primary py-3 text-white">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4 px-4">
                    <p><strong>Alamat:</strong> Jl. Kraton Industri Raya No.11, Curah Dukuh Barat, Curahdukuh, Kec.
                        Kraton, Pasuruan, Jawa Timur 67151</p>
                    <p><strong>Telepon:</strong> (0343) 4502000</p>
                </div>
            </div>
        </div>
        <div class="container-fluid text-center">
            <p class="mb-0">&copy; Iseki Indonesia.</p>
        </div>
    </footer>

    <script src="{{ asset('bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- QR Code Library -->
    <script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/qrcode.min.js') }}"></script>
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script> --}}

    <!-- QR Code Generation Script -->
    <!-- QR Code Scan NIK -->
    <script>
        var element = document.getElementById('memberLoginForm');
        var width = element.offsetWidth;

        const nikScanner = new Html5QrcodeScanner("reader_nik", {
            fps: 10,
            qrbox: {
                width: width,
                height: width,
            },
        });

        function onScanSuccess(decodedText, decodedResult) {
            // Ambil bagian pertama dari decodedText sebelum ;
            const nik = decodedText.split(';')[0].trim();

            // Isi input NIK
            const input = document.getElementById("NIK_Input");
            input.value = nik;

            // Hapus scanner
            nikScanner.clear();

            // Submit form
            document.getElementById("memberLoginForm").submit();
        }

        document.getElementById("scanNIK").addEventListener("click", () => {
            nikScanner.render(onScanSuccess);
        });
    </script>

</body>
</html>
