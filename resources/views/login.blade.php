<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iseki</title>
    <link rel="icon" type="image/x-icon" href="{{asset('img/logo-iseki.png')}}">
    <link href="{{asset('bootstrap/css/bootstrap.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('style/style.css')}}">
    <style>
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
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary" id="login">
        <div class="container">
            <a class="navbar-brand" href="{{ route('login') }}"><b>ISEKI</b></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="collapsibleNavbar">
                <ul class="navbar-nav ms-auto">
                    <a class="nav-link" href="#login">
                        <li class="nav-item px-2">
                            Login
                        </li>
                    </a>
                    <a class="nav-link" href="#about">
                        <li class="nav-item px-2">
                            About
                        </li>
                    </a>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid bg-image" style="background-image: url('{{asset('img/bg.png')}}');">
        <div class="row h-100 align-items-center">
            <div class="col-md-4 offset-md-1 text-white">
                <h1>Iseki Indonesia</h1>
                <h2>Scanner Code</h2>
                <p>Website untuk monitoring rak dan item part.</p>
            </div>
            <div class="col-md-4 offset-md-1">
                <div class="card login-card">
                    <div class="card-body">
                        <h2 class="card-title text-center">Login</h2>
                        @if ($errors->any())
                        <div>
                            @foreach ($errors->all() as $error)
                            <p style="color:red;">{{ $error }}</p>
                            @endforeach
                        </div>
                        @endif
                        <form hr action="{{ route('login.auth') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <input type="text" class="form-control" placeholder="Username" name="Username_User">
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" placeholder="Password" name="Password_User">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    <div class="container-fluid">
        <div class="row align-items-center text-center pt-4 mb-2" id="about">
            <h3><b>Iseki</b> Scanner</h3>
            <p>Merupakan website yang digunakan oleh PT. Iseki Indonesia dalam melakukan monitoring rak dan item part
                dengan
                melakukan scan pada item code.</p>
        </div>
        <div class="row px-4 mb-4">
            <div class="col-sm-4 mb-4">
                <div class="card card-shadow text-center">
                    <div class="card-body">
                        <h4>Use</h4>
                        <p>Gunakan part yang ada di rak.</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 mb-4">
                <div class="card card-shadow text-center">
                    <div class="card-body">
                        <h4>Put</h4>
                        <p>Letakkan part sesuai dengan tempatnya.</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 mb-4">
                <div class="card card-shadow text-center">
                    <div class="card-body">
                        <h4>Scan</h4>
                        <p>Scan kode item dan rak untuk memonitoring part.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    <footer class="bg-primary py-3 text-white">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4 px-4">
                    <p><strong>Alamat:</strong> Jl. Kraton Industri Raya No.11, Curah Dukuh Barat, Curahdukuh, Kec.
                        Kraton,
                        Pasuruan, Jawa Timur 67151</p>
                    <p><strong>Telepon:</strong> (0343) 4502000</p>
                </div>
            </div>
        </div>
        <div class="container-fluid text-center">
            <p class="mb-0">&copy; Iseki Indonesia.</p>
        </div>
    </footer>
    <script src="{{asset('bootstrap/js/bootstrap.min.js')}}"></script>
</body>

</html>
