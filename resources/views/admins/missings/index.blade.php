@extends('layouts.main')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <div class="marquee-container">
        <div class="marquee">
            <span>Missing List</span>
            <span>Missing List</span>
            <span>Missing List</span>
            <span>Missing List</span>
            <span>Missing List</span>
            <!-- duplikat lagi biar seamless -->
            <span>Missing List</span>
            <span>Missing List</span>
            <span>Missing List</span>
            <span>Missing List</span>
            <span>Missing List</span>
        </div>
    </div>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">

        {{-- <form action="{{ route('admin_submission.export') }}" method="GET" target="_blank" class="mr-2">
            <input name="Day_Request_Hidden" type="hidden" value="{{ $date }}">
            <button class="d-sm-inline-block btn btn-md btn-primary shadow-sm" type="submit">
                <i class="fas fa-download fa-sm text-white-50"></i> Download Request
            </button>
        </form> --}}

        {{-- <form action="{{ route('admin_submission.reset') }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="Day_Request" value="{{ $date }}">
            <button class="btn btn-danger btn-md shadow-sm" type="submit" onclick="return confirm('Are you sure want to reset this submission data?')">
                <i class="fas fa-trash-alt"></i> Reset Request
            </button>
        </form> --}}
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Missing List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead class="text-primary">
                        <tr>
                            <th>No</th>
                            <th>Rack</th>
                            <th>Name</th>
                            <th>Time Request</th>
                            <th>Overdue</th>
                            <th>PIC</th>
                        </tr>
                    </thead>
                    {{-- <tfoot>
                        <tr>
                            <th>No</th>
                            <th>Rack</th>
                            <th>Name</th>
                            <th>Time Request</th>
                            <th>Overdue</th>
                            <th>PIC</th>
                        </tr>
                    </tfoot> --}}
                    <tbody>
                        @foreach ($requests as $s)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $s->Code_Rack }}</td>
                            <td>{{ $s->rack->Name_Item_Rack ?? '' }}</td>
                            <td>{{ $s->Day_Request }} {{ $s->Time_Request }}</td>
                            @php
                                $now = \Carbon\Carbon::now();
                                $requestDateTime = \Carbon\Carbon::parse($s->Day_Request . ' ' . $s->Time_Request);
                                $interval = $requestDateTime->diff($now);
                            @endphp

                            <td class="text-danger font-weight-bold overdue">
                                {{ $interval->d ? $interval->d . ' day(s) ' : '' }}
                                {{ $interval->h ? $interval->h . ' hour(s) ' : '' }}
                                {{ $interval->i ? $interval->i . ' minute(s) ' : '' }}
                            </td>
                            <td>{{ $s->member->Name_Member ?? '' }}</td>
                            {{-- <td>{{ optional($s->record)->Day_Record ?? '' }} {{ optional($s->record)->Time_Record ?? '' }}</td>
                            <td>{{ $s->Code_Item_Rack }}</td>
                            <td>{{ $s->Sum_Request }}</td>
                            <td>{{  optional($s->record)->Sum_Record ?? '' }}</td>
                            <td>{{ $s->Updated_At_Request ?? '' }}</td> --}}
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('style')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<style>
  .marquee-container {
    position: relative;
    width: 100%;
    overflow: hidden;
    padding: 10px 0;
  }

  .marquee {
    display: flex;
    width: max-content;
    animation: marquee 30s linear infinite;
  }

  .marquee span {
    font-size: 5vw; /* gede, responsif */
    font-weight: 900;
    text-transform: uppercase;
    background: linear-gradient(90deg, red, indigo, violet, red);
    background-size: 300% auto; /* penting biar bisa bergerak */
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    padding: 0 2rem;
    white-space: nowrap;
    animation: colorChange 6s linear infinite;
  }

  @keyframes marquee {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
  }

  @keyframes colorChange {
    0%   { background-position: 0% 50%; }
    50%  { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }

  /* Hilangkan padding bawaan table */
  table th,
  table td {
      vertical-align: middle;
  }

  /* Header biar besar full seukuran kolom */
  table th {
      font-size: 3rem; /* gede sesuai kebutuhan */
      white-space: nowrap;
      text-align: center;
      padding-right: 0 !important;
      padding-left: 0 !important;
  }

  /* Kolom overdue custom */
  table td.overdue {
      font-size: 1.5rem;
      font-weight: bold;
      color: red;
      width:1%;
      white-space: nowrap;
  }
</style>
@endsection

@section('script')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('js/demo/datatables-demo.js') }}"></script>
@endsection
