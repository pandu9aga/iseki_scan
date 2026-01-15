@extends('layouts.mc')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <div class="marquee-container">
        <div class="marquee">
            <span>Missing List MC</span>
            <span>Missing List MC</span>
            <span>Missing List MC</span>
            <span>Missing List MC</span>
            <span>Missing List MC</span>
            <!-- duplikat lagi biar seamless -->
            <span>Missing List MC</span>
            <span>Missing List MC</span>
            <span>Missing List MC</span>
            <span>Missing List MC</span>
            <span>Missing List MC</span>
        </div>
    </div>

    <div class="row">
        <div class="card mb-4 col-md-4 col-lg-3">
            <div class="card-header">
                <div class="font-weight-bold text-primary text-uppercase">
                    Update Ready Stock
                </div>
            </div>
            <div class="card-body">
                <div>
                    <form action="{{ route('mc_submission.upload_ready') }}" method="POST" enctype="multipart/form-data" class="d-inline">
                        @csrf
                        <div class="input-group">
                            <input type="file" name="ready_excel" class="form-control" accept=".xlsx,.xls" required>
                            <button class="btn btn-success ml-1" type="submit">Upload</button>
                        </div>
                        @if ($errors->has('ready_excel'))
                            <div class="text-danger mt-1">{{ $errors->first('ready_excel') }}</div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4 col-md-4 col-lg-3">
        <form action="{{ route('mc.missing.mc.export') }}" method="GET" target="_blank" class="mr-2">
            <input name="Day_Request_Hidden" type="hidden" value="{{ $date }}">
            <button class="d-sm-inline-block btn btn-md btn-primary shadow-sm" type="submit">
                <i class="fas fa-download fa-sm text-white-50"></i> Download Missing MC
            </button>
        </form>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Missing List MC</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead class="text-primary">
                        <tr>
                            <th>No</th>
                            <th>Rack</th>
                            <th>Item</th>
                            <th>Name</th>
                            <th>Sum</th>
                            <th>Time Request</th>
                            <th>Overdue</th>
                            <th>PIC</th>
                        </tr>
                    </thead>
                        @foreach ($missingRequests as $s)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $s->Code_Rack }}</td>
                            <td>{{ $s->Code_Item_Rack }}</td>
                            <td>{{ $s->rack->Name_Item_Rack ?? '' }}</td>
                            <td>{{ $s->Sum_Request }}</td>
                            <td>{{ $s->Day_Request }} {{ $s->Time_Request }}</td>
                            <td class="text-danger font-weight-bold overdue">
                                @php
                                    if ($s->Day_Request && $s->Time_Request) {
                                        $statusTime = \Carbon\Carbon::parse($s->Day_Request . ' ' . $s->Time_Request);
                                        $now = \Carbon\Carbon::now();
                                        $totalSeconds = $now->timestamp - $statusTime->timestamp;

                                        if ($totalSeconds <= 0) {
                                            echo 'On time';
                                        } else {
                                            $days = floor($totalSeconds / 86400);
                                            $hours = floor(($totalSeconds % 86400) / 3600);
                                            $minutes = floor(($totalSeconds % 3600) / 60);

                                            $parts = [];
                                            if ($days > 0) $parts[] = $days . ' day(s)';
                                            if ($hours > 0) $parts[] = $hours . ' hour(s)';
                                            if ($minutes > 0) $parts[] = $minutes . ' minute(s)';

                                            echo implode(' ', $parts);
                                        }
                                    } else {
                                        echo '-';
                                    }
                                @endphp
                            </td>
                            <td>{{ $s->member->Name_Member ?? '' }}</td>
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
      font-size: 2rem;
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
