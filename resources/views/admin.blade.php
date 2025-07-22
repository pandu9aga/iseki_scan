<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iseki</title>
    <link rel="icon" type="image/x-icon" href="{{asset('img/logo-iseki.png')}}">
    <link href="{{asset('bootstrap/css/bootstrap.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('style/style.css')}}">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary" id="login">
        <div class="container">
            <a class="navbar-brand" href="{{ route('login') }}"><b>ISEKI</b></a>
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
                        <h2 class="card-title text-center">Register</h2>
                        @if ($errors->any())
                        <div>
                            @foreach ($errors->all() as $error)
                            <p style="color:red;">{{ $error }}</p>
                            @endforeach
                        </div>
                        @endif
                        <form hr action="{{ route('admin.create') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <input type="text" class="form-control" placeholder="Name" name="Name_User">
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control" placeholder="Username" name="Username_User">
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" placeholder="Password" name="Password_User">
                            </div>
                            <div class="mb-3">
                                <select name="Id_Type_User" class="form-control">
                                    @foreach ($type_user as $type)
                                        <option value="{{ $type->Id_Type_User }}">
                                            {{ $type->Name_Type_User }}
                                        </option>
                                    @endforeach
                                </select>
                            </div> 
                            <button type="submit" class="btn btn-primary w-100">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
            <p class="mb-0">&copy; 2025 Iseki Indonesia.</p>
        </div>
    </footer>
    <script src="{{asset('bootstrap/js/bootstrap.min.js')}}"></script>
</body>

</html>
