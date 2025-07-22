@extends('layouts.main')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">User</h1>
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col">
                    <div class="p-5">
                        <form class="user" action="{{ route('user.create') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6 col-md-6">
                                    <div class="form-group mb-3">
                                        <span style="font-size: small;">Name</span>
                                        <input type="text" name="Name_User" class="form-control form-control-user @error('Name_User') is-invalid @enderror" value="{{ old('Name_User') }}">
                                        @error('Name_User')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-group mb-3">
                                        <span style="font-size: small;">Username</span>
                                        <input type="text" id="Username_User" name="Username_User" class="form-control form-control-user @error('Username_User') is-invalid @enderror" value="{{ old('Username_User') }}">
                                        @error('Username_User')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-group mb-3">
                                        <span style="font-size: small;">Password</span>
                                        <input type="password" name="Password_User" class="form-control form-control-user @error('Password_User') is-invalid @enderror" value="{{ old('Password_User') }}">
                                        @error('Password_User')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-group mb-3">
                                        <span style="font-size: small;">Type</span>
                                        <select name="Id_Type_User" class="form-control form-control-user @error('Id_Type_User') is-invalid @enderror"
                                            style="display: block;
                                                    width: 100%;
                                                    height: calc(2.9em + .75rem + 2px);
                                                    padding: 0.375rem .75rem;">
                                            @foreach ($type_user as $type)
                                                <option value="{{ $type->Id_Type_User }}">
                                                    {{ $type->Name_Type_User }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('Id_Type_User')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>                                    
                                </div>
                            </div>
                            <br>
                            <button type="submit" class="btn btn-info btn-user" style="padding-left: 50px; padding-right: 50px;">
                                Save
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->
@endsection