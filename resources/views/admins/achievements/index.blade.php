@extends('layouts.main')

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Achievement</h1>
            <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                <form action="{{ route('achievement') }}" method="GET" class="form-inline d-inline">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="month" name="month" class="form-control form-control-sm mr-2" value="{{ $month }}">
                    <button type="submit" class="btn btn-sm btn-primary mr-2">
                        <i class="fas fa-calendar-alt fa-sm mr-1"></i>Filter Month
                    </button>
                </form>
                <a href="{{ route('achievement.export', ['month' => $month]) }}" class="btn btn-sm btn-success shadow-sm">
                    <i class="fas fa-download fa-sm text-white-50 mr-1"></i> Export Excel
                </a>
            </div>
        </div>

        {{-- Filter Date Card --}}
        <div class="card border-left-primary shadow mb-4">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: 10px;">
                    <div>
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Daily Summary Date
                        </div>
                        <form action="{{ route('achievement') }}" method="GET" id="dateFilterForm" class="d-flex flex-wrap align-items-center" style="gap: 6px;">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeDate(-1)" title="Previous Day">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <input name="date" id="dateInput" type="date" class="form-control form-control-sm mx-1" style="width: auto; min-width: 140px;" value="{{ $selectedDate }}">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeDate(1)" title="Next Day">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info ml-1" onclick="setToday()" title="Today">
                                    <i class="fas fa-calendar-day"></i>
                                </button>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary shadow-sm">
                                <i class="fas fa-filter fa-sm mr-1"></i>Apply Date
                            </button>
                        </form>
                    </div>
                    <div class="text-right">
                        <span class="badge badge-light border px-3 py-2 text-dark font-weight-bold" style="font-size: 0.95rem;">
                            <i class="fas fa-calendar-check text-primary mr-1"></i> {{ $formattedSelectedDate }}
                        </span>
                    </div>
                </div>
                {{-- 5 Summary Cards Row --}}
                <div class="row mt-3 summary-cards-row">
                    <!-- Total Request Card -->
                    <div class="col-6 col-md-4 col-xl mb-2 mb-xl-0">
                        <div class="card border-left-pink shadow h-100 py-1 py-md-2 summary-stat-card">
                            <div class="card-body py-2 px-2 px-md-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-1 mr-md-2">
                                        <div class="text-xs font-weight-bold text-pink text-uppercase mb-1 stat-title" title="Jumlah Request">
                                            Request
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 stat-val">{{ $totalDailyRequests }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bullhorn fa-2x text-gray-300 stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ready Card -->
                    <div class="col-6 col-md-4 col-xl mb-2 mb-xl-0">
                        <div class="card border-left-success shadow h-100 py-1 py-md-2 summary-stat-card">
                            <div class="card-body py-2 px-2 px-md-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-1 mr-md-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1 stat-title" title="Ready">
                                            Ready
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 stat-val">{{ $totalDailyReady }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300 stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Card -->
                    <div class="col-6 col-md-4 col-xl mb-2 mb-xl-0">
                        <div class="card border-left-info shadow h-100 py-1 py-md-2 summary-stat-card">
                            <div class="card-body py-2 px-2 px-md-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-1 mr-md-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1 stat-title" title="Shipping">
                                            Shipping
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 stat-val">{{ $totalDailyShipping }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-truck fa-2x text-gray-300 stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Perubahan Desain Card -->
                    <div class="col-6 col-md-6 col-xl mb-2 mb-xl-0">
                        <div class="card border-left-warning shadow h-100 py-1 py-md-2 summary-stat-card">
                            <div class="card-body py-2 px-2 px-md-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-1 mr-md-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1 stat-title" title="Perubahan Desain">
                                            Perubahan Desain
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 stat-val">{{ $totalDailyDesignChanges }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-pencil-ruler fa-2x text-gray-300 stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rekap Record Card -->
                    <div class="col-6 col-md-6 col-xl mb-2 mb-xl-0">
                        <div class="card border-left-navy shadow h-100 py-1 py-md-2 summary-stat-card">
                            <div class="card-body py-2 px-2 px-md-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-1 mr-md-2">
                                        <div class="text-xs font-weight-bold text-navy text-uppercase mb-1 stat-title" title="Rekap Record">
                                            Record
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 stat-val">{{ $totalDailyRecords }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-qrcode fa-2x text-gray-300 stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Daily Summary Table Card --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-table mr-1"></i>Daily Summary - {{ \Carbon\Carbon::parse($month)->format('F Y') }}
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="achievementTableDailySummary" width="100%" cellspacing="0">
                        <thead>
                            <tr class="text-center text-white">
                                <th class="sticky-col bg-secondary text-white" style="width: 10%;">Date</th>
                                <th class="bg-pink text-white" style="width: 18%;">Request</th>
                                <th class="bg-success text-white" style="width: 18%;">Ready</th>
                                <th class="bg-info text-white" style="width: 18%;">Shipping</th>
                                <th class="bg-warning text-white" style="width: 18%;">Perubahan Desain</th>
                                <th class="bg-navy text-white" style="width: 18%;">Record</th>
                            </tr>
                            <tr class="sticky-total" style="background-color: #e3e6f0; font-weight: bold;">
                                <th class="text-center italic sticky-col"><b>TOTAL</b></th>
                                <th class="text-center text-pink font-weight-bold">{{ $monthlySummary['totals']['request'] }}</th>
                                <th class="text-center text-success font-weight-bold">{{ $monthlySummary['totals']['ready'] }}</th>
                                <th class="text-center text-info font-weight-bold">{{ $monthlySummary['totals']['shipping'] }}</th>
                                <th class="text-center text-warning font-weight-bold">{{ $monthlySummary['totals']['design_change'] }}</th>
                                <th class="text-center text-navy font-weight-bold">{{ $monthlySummary['totals']['record'] }}</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="sticky-total" style="background-color: #e3e6f0; color: #333; font-weight: bold;">
                                <th class="text-center">TOTAL</th>
                                <th class="text-center text-pink">{{ $monthlySummary['totals']['request'] }}</th>
                                <th class="text-center text-success">{{ $monthlySummary['totals']['ready'] }}</th>
                                <th class="text-center text-info">{{ $monthlySummary['totals']['shipping'] }}</th>
                                <th class="text-center text-warning">{{ $monthlySummary['totals']['design_change'] }}</th>
                                <th class="text-center text-navy">{{ $monthlySummary['totals']['record'] }}</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            @for($i = 1; $i <= $daysInMonth; $i++)
                                @php
                                    $dayData = $monthlySummary['days'][$i];
                                @endphp
                                <tr class="hover-row text-center">
                                    <td class="text-center sticky-col font-weight-bold"><b>{{ $i }}</b></td>
                                    <td class="font-weight-bold">{{ $dayData['request'] }}</td>
                                    <td class="font-weight-bold">{{ $dayData['ready'] }}</td>
                                    <td class="font-weight-bold">{{ $dayData['shipping'] }}</td>
                                    <td class="font-weight-bold">{{ $dayData['design_change'] }}</td>
                                    <td class="font-weight-bold">{{ $dayData['record'] }}</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Member Achievement Report -
                    {{ \Carbon\Carbon::parse($month)->format('F Y') }}
                </h6>
            </div>
            <div class="card-body">
                @if(count($requestsData) == 0)
                    <div class="alert alert-info">
                        No active members found for this period.
                    </div>
                @else
                    <div class="table-responsive mb-5">
                        <table class="table table-bordered" id="achievementTableRequests" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="align-middle text-center sticky-col"
                                        style="background-color: #f8f9fc;">Date
                                    </th>
                                    <th colspan="{{ count($requestsData) }}" class="text-center bg-primary text-white">REQUESTS & CHECKS
                                    </th>
                                </tr>
                                <tr>
                                    <!-- User names for Requests -->
                                    @foreach($requestsData as $userId => $data)
                                        <th class="text-center small">{{ $data['name'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Total Row -->
                                <tr class="sticky-total" style="background-color: #e3e6f0; font-weight: bold;">
                                    <td class="text-center italic sticky-col"><b>TOTAL</b></td>
                                    @foreach($requestsData as $userId => $data)
                                        <td class="text-center">{{ $data['total'] }}</td>
                                    @endforeach
                                </tr>
                                <!-- Daily Rows -->
                                @for($i = 1; $i <= $daysInMonth; $i++)
                                    <tr class="hover-row">
                                        <td class="text-center sticky-col align-middle"><b>{{ $i }}</b></td>
                                        <!-- Days for Requests -->
                                        @foreach($requestsData as $userId => $data)
                                            <td class="p-0">
                                                <div class="d-flex align-items-stretch" style="height: 100%; min-height: 44px;">
                                                    <div class="d-flex align-items-center justify-content-center border-right small" style="flex: 1; font-weight: bold; background-color: rgba(0,0,0,0.02);" title="Total (Requests + Checks)">
                                                        {{ $data['days'][$i] + $data['days_check'][$i] }}
                                                    </div>
                                                    <div class="d-flex flex-column" style="width: 45px;">
                                                        <div class="d-flex align-items-center justify-content-center border-bottom small" style="flex: 1; color: #df4e97; font-weight: bold;" title="Requests">{{ $data['days'][$i] }}</div>
                                                        <div class="d-flex align-items-center justify-content-center small" style="flex: 1; color: #4e73df; font-weight: bold;" title="Checks">{{ $data['days_check'][$i] }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="achievementTableRecords" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="align-middle text-center sticky-col"
                                        style="background-color: #f8f9fc;">Date
                                    </th>
                                    <th colspan="{{ count($recordsData) }}" class="text-center bg-success text-white">RECORDS
                                    </th>
                                </tr>
                                <tr>
                                    <!-- User names for Records -->
                                    @foreach($recordsData as $userId => $data)
                                        <th class="text-center small">{{ $data['name'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Total Row -->
                                <tr class="sticky-total" style="background-color: #e3e6f0; font-weight: bold;">
                                    <td class="text-center italic sticky-col"><b>TOTAL</b></td>
                                    @foreach($recordsData as $userId => $data)
                                        <td class="text-center">{{ $data['total'] }}</td>
                                    @endforeach
                                </tr>
                                <!-- Daily Rows -->
                                @for($i = 1; $i <= $daysInMonth; $i++)
                                    <tr class="hover-row">
                                        <td class="text-center sticky-col"><b>{{ $i }}</b></td>
                                        <!-- Days for Records -->
                                        @foreach($recordsData as $userId => $data)
                                            <td class="text-center">{{ $data['days'][$i] }}</td>
                                        @endforeach
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('style')
    <style>
        #achievementTable th,
        #achievementTable td {
            vertical-align: middle;
            padding: 0.5rem;
        }

        .table-responsive {
            max-height: 700px;
            overflow-y: auto;
        }

        thead th, .sticky-total th, .sticky-total td {
            box-shadow: inset 0 0 0 1px #e3e6f0 !important;
            border: none !important; /* Mencegah double border dengan box-shadow */
        }

        thead tr:nth-child(1) th {
            position: sticky;
            z-index: 11;
            background-color: #f8f9fc;
        }

        thead tr:nth-child(2) th {
            position: sticky;
            z-index: 10;
            background-color: #f8f9fc;
        }

        .sticky-total td, .sticky-total th {
            position: sticky;
            z-index: 9;
            background-color: #e3e6f0 !important;
        }

        .sticky-total td.sticky-col, .sticky-total th.sticky-col {
            z-index: 12 !important;
            left: 0;
            background-color: #e3e6f0 !important;
        }

        .sticky-col {
            position: sticky;
            left: 0;
            background-color: #f8f9fc !important;
            z-index: 5;
            /* box-shadow already provides the border */
        }

        thead tr:nth-child(1) th.sticky-col {
            z-index: 15;
        }

        thead tr:nth-child(2) th.sticky-col {
            z-index: 14;
        }

        tbody tr:not(.sticky-total):hover td {
            background-image: linear-gradient(0deg, rgba(0, 0, 0, 0.05) 0%, rgba(0, 0, 0, 0.05) 100%);
        }

        tbody tr:not(.sticky-total):hover td.sticky-col {
            background-image: linear-gradient(0deg, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.1) 100%);
        }

        /* Custom Pink & Navy styling */
        .border-left-pink {
            border-left: 0.25rem solid #df4e97 !important;
        }
        .text-pink {
            color: #df4e97 !important;
        }
        .bg-pink {
            background-color: #df4e97 !important;
        }

        .border-left-navy {
            border-left: 0.25rem solid #4e73df !important;
        }
        .text-navy {
            color: #4e73df !important;
        }
        .bg-navy {
            background-color: #4e73df !important;
        }

        /* Specific for Daily Summary Table Sticky Headers */
        #achievementTableDailySummary thead tr:nth-child(1) th:nth-child(1) {
            background-color: #5a5c69 !important;
            color: #ffffff !important;
        }
        #achievementTableDailySummary thead tr:nth-child(1) th:nth-child(2) {
            background-color: #df4e97 !important; /* Pink Primary */
            color: #ffffff !important;
        }
        #achievementTableDailySummary thead tr:nth-child(1) th:nth-child(3) {
            background-color: #1cc88a !important;
            color: #ffffff !important;
        }
        #achievementTableDailySummary thead tr:nth-child(1) th:nth-child(4) {
            background-color: #36b9cc !important;
            color: #ffffff !important;
        }
        #achievementTableDailySummary thead tr:nth-child(1) th:nth-child(5) {
            background-color: #f6c23e !important;
            color: #ffffff !important;
        }
        #achievementTableDailySummary thead tr:nth-child(1) th:nth-child(6) {
            background-color: #4e73df !important; /* Biru Navy */
            color: #ffffff !important;
        }

        #achievementTableDailySummary thead tr:nth-child(2) th,
        #achievementTableDailySummary thead tr:nth-child(2) td {
            background-color: #e3e6f0 !important;
        }

        #achievementTableDailySummary thead tr:nth-child(2) th.sticky-col,
        #achievementTableDailySummary thead tr:nth-child(2) td.sticky-col {
            background-color: #e3e6f0 !important;
            color: #333333 !important;
        }

        #achievementTableDailySummary .text-pink {
            color: #df4e97 !important;
        }
        #achievementTableDailySummary .text-navy {
            color: #4e73df !important;
        }
        #achievementTableDailySummary .text-success {
            color: #1cc88a !important;
        }
        #achievementTableDailySummary .text-info {
            color: #36b9cc !important;
        }
        #achievementTableDailySummary .text-warning {
            color: #f6c23e !important;
        }
        #achievementTableDailySummary .text-dark {
            color: #3a3b45 !important;
        }

        /* Mobile Responsive for Summary Cards */
        @media (max-width: 767.98px) {
            .summary-cards-row {
                margin-top: 0.6rem !important;
                margin-left: -4px !important;
                margin-right: -4px !important;
            }
            .summary-cards-row > [class*="col-"] {
                padding-left: 4px !important;
                padding-right: 4px !important;
                margin-bottom: 6px !important;
            }
            .summary-stat-card {
                border-left-width: 0.25rem !important;
                border-radius: 0.35rem !important;
            }
            .summary-stat-card .card-body {
                padding: 0.35rem 0.55rem !important;
            }
            .summary-stat-card .stat-title {
                font-size: 0.62rem !important;
                line-height: 1.15 !important;
                margin-bottom: 2px !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .summary-stat-card .stat-val {
                font-size: 1rem !important;
                line-height: 1.2 !important;
            }
            .summary-stat-card .stat-icon {
                font-size: 1.15rem !important;
            }
        }
    </style>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function adjustStickyHeaders(tableId) {
                var table = document.getElementById(tableId);
                if (!table) return;

                var row1 = table.querySelector('thead tr:nth-child(1)');
                var row2 = table.querySelector('thead tr:nth-child(2)');
                var totalRow = table.querySelector('tbody tr.sticky-total');

                if (row1) {
                    var h1 = row1.getBoundingClientRect().height;
                    var ths1 = row1.querySelectorAll('th, td');
                    ths1.forEach(function(th) { th.style.top = '0px'; });

                    if (row2) {
                        var h2 = row2.getBoundingClientRect().height;
                        var ths2 = row2.querySelectorAll('th, td');
                        ths2.forEach(function(th) { th.style.top = h1 + 'px'; });

                        if (totalRow) {
                            var tds = totalRow.querySelectorAll('td, th');
                            tds.forEach(function(td) { td.style.top = (h1 + h2) + 'px'; });
                        }
                    }
                }
            }

            // Adjust all tables
            adjustStickyHeaders('achievementTableDailySummary');
            adjustStickyHeaders('achievementTableRequests');
            adjustStickyHeaders('achievementTableRecords');

            // Re-adjust on window resize just in case text wraps differently
            window.addEventListener('resize', function() {
                adjustStickyHeaders('achievementTableDailySummary');
                adjustStickyHeaders('achievementTableRequests');
                adjustStickyHeaders('achievementTableRecords');
            });
        });

        function changeDate(offset) {
            var input = document.getElementById('dateInput');
            if (!input) return;
            var d = new Date(input.value + 'T00:00:00');
            d.setDate(d.getDate() + offset);
            input.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            document.getElementById('dateFilterForm').submit();
        }

        function setToday() {
            var input = document.getElementById('dateInput');
            if (!input) return;
            var d = new Date();
            input.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            document.getElementById('dateFilterForm').submit();
        }
    </script>
@endsection