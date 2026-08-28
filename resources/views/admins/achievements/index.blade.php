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
                        <div class="card border-left-primary shadow h-100 py-1 py-md-2 summary-stat-card">
                            <div class="card-body py-2 px-2 px-md-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-1 mr-md-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1 stat-title" title="Jumlah Request">
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
                        <div class="card border-left-dark shadow h-100 py-1 py-md-2 summary-stat-card">
                            <div class="card-body py-2 px-2 px-md-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-1 mr-md-2">
                                        <div class="text-xs font-weight-bold text-dark text-uppercase mb-1 stat-title" title="Rekap Record">
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

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Achievement Report -
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

                if (row1 && row2 && totalRow) {
                    var h1 = row1.getBoundingClientRect().height;
                    var h2 = row2.getBoundingClientRect().height;

                    // Set top row 1
                    var ths1 = row1.querySelectorAll('th');
                    ths1.forEach(function(th) { th.style.top = '0px'; });

                    // Set top row 2
                    var ths2 = row2.querySelectorAll('th');
                    ths2.forEach(function(th) { th.style.top = h1 + 'px'; });

                    // Set top total row
                    var tds = totalRow.querySelectorAll('td, th');
                    tds.forEach(function(td) { td.style.top = (h1 + h2) + 'px'; });
                }
            }

            // Adjust both tables
            adjustStickyHeaders('achievementTableRequests');
            adjustStickyHeaders('achievementTableRecords');

            // Re-adjust on window resize just in case text wraps differently
            window.addEventListener('resize', function() {
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