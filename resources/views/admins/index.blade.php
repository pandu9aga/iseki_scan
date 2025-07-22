@extends('layouts.main')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <!-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i
                class="fas fa-download fa-sm text-white-50"></i> Generate Report</a> -->
    </div>

    <!-- Content Row -->
    <div class="row">

        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        @php
                            use Carbon\Carbon;
                            $today = Carbon::now()->locale('en')->isoFormat('dddd, D-MMM-YY');
                        @endphp
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Date</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $today }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Time</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="realTimeClock">--:--:--</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Today's Record</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalRecords ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->

    <br>

    <!-- Content Row -->
    <div class="row">

        <!-- Content Column -->
        <div class="col-lg-9 mb-4">

            <!-- Project Card Example -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Today's Accuracy</h6>
                </div>
                <div class="card-body">
                    <h4 class="small font-weight-bold">Correct <span class="float-right">{{ $correct }}</span></h4>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-gradient-success" role="progressbar"
                            style="width: {{ $maxProgress > 0 ? ($correct / $maxProgress) * 100 : 0 }}%"
                            aria-valuenow="{{ $correct }}" 
                            aria-valuemin="0" 
                            aria-valuemax="{{ $maxProgress }}">
                        </div>
                    </div>
                    <h4 class="small font-weight-bold">Incorrect <span class="float-right">{{ $incorrect }}</span></h4>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-gradient-danger" role="progressbar"
                            style="width: {{ $maxProgress > 0 ? ($incorrect / $maxProgress) * 100 : 0 }}%"
                            aria-valuenow="{{ $incorrect }}" 
                            aria-valuemin="0" 
                            aria-valuemax="{{ $maxProgress }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<script>
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('realTimeClock').textContent = `${hours}:${minutes}:${seconds}`;
    }

    setInterval(updateClock, 1000); // Update every 1 second
    updateClock(); // Run immediately to avoid delay
</script>
@endsection
