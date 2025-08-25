@extends('layouts.user')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">Home</h1>
    <!-- Page Heading -->
    <div class="row mt-4">
        <a href="{{ route('request') }}">
            <button class="btn btn-lg btn-primary shadow-sm ms-auto mx-4 my-2" type="button">
                <i class="fas fa-bullhorn fa-sm text-white-50"></i> Request
            </button>
        </a>
    </div>
    <div class="row mt-4">
        <a href="{{ route('record') }}">
            <button class="btn btn-lg btn-primary shadow-sm ms-auto mx-4 my-2" type="button">
                <i class="fas fa-qrcode fa-sm text-white-50"></i> Record
            </button>
        </a>
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
<script src="{{asset('js/demo/datatables-demo.js')}}"></script>
@endsection