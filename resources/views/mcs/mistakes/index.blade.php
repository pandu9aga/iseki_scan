@extends('layouts.mc')

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Mistake Report (Daily)</h1>
            <div>
                <form action="{{ route('mc_mistake') }}" method="GET" class="form-inline d-inline">
                    <input type="month" name="month" class="form-control mr-1" value="{{ $month }}">
                    <button type="submit" class="btn btn-primary mr-1">Submit</button>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 font-weight-bold text-danger">Daily Total Mistakes -
                            {{ \Carbon\Carbon::parse($month)->format('F Y') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="height: 300px;">
                            <canvas id="dailyTotalChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 font-weight-bold text-primary">Monthly Accumulation Trend -
                            {{ \Carbon\Carbon::parse($month)->format('F Y') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="height: 300px;">
                            <canvas id="mistakeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                @foreach($categories as $cat)
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-primary">
                            <h6 class="m-0 font-weight-bold text-white text-uppercase">{{ $cat }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm daily-table" width="100%" cellspacing="0">
                                    <thead class="bg-light text-center">
                                        <tr>
                                            <th rowspan="2" class="align-middle" style="min-width: 150px;">Member Name</th>
                                            <th rowspan="2" class="align-middle bg-dark text-white">Total</th>
                                            <th colspan="{{ $daysInMonth }}">Date
                                                ({{ \Carbon\Carbon::parse($month)->format('F Y') }})</th>
                                        </tr>
                                        <tr>
                                            @for($i = 1; $i <= $daysInMonth; $i++)
                                                <th class="date-col">{{ $i }}</th>
                                            @endfor
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($reportData[$cat] as $memberId => $data)
                                            <tr>
                                                <td class="font-weight-bold">
                                                    {{ $data['name'] }}
                                                </td>
                                                <td class="text-center bg-gray-100 font-weight-bold">
                                                    {{ $data['total'] }}
                                                </td>
                                                @for($i = 1; $i <= $daysInMonth; $i++)
                                                    <td
                                                        class="text-center {{ $data['days'][$i] > 0 ? 'bg-danger text-white' : 'text-gray-300' }}">
                                                        @if($data['days'][$i] > 0)
                                                            <span class="text-white font-weight-bold d-block">
                                                                {{ $data['days'][$i] }}
                                                            </span>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                @endfor
                                            </tr>
                                        @endforeach

                                        @if(count($reportData[$cat]) == 0)
                                            <tr>
                                                <td colspan="{{ $daysInMonth + 2 }}" class="text-center">No records found for this
                                                    category.</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('vendor/chart.js/Chart.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            const colors = [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#858796', '#5a5c69', '#6610f2', '#6f42c1', '#e83e8c',
                '#fd7e14', '#20c997', '#007bff', '#6c757d', '#28a745'
            ];

            var ctx = document.getElementById("mistakeChart");
            if (ctx) {
                var mistakeChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [@for($i = 1; $i <= $daysInMonth; $i++) "{{ $i }}", @endfor],
                        datasets: [
                            @foreach($chartData as $index => $dataset)
                                        {
                                    label: "{{ $dataset['label'] }}",
                                    borderColor: colors[{{ $index }} % colors.length],
                                    backgroundColor: colors[{{ $index }} % colors.length],
                                    data: {!! json_encode($dataset['data']) !!},
                                    fill: false,
                                    lineTension: 0.1,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    pointBackgroundColor: colors[{{ $index }} % colors.length],
                                },
                            @endforeach
                            ],
                    },
                    options: {
                        maintainAspectRatio: false,
                        layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
                        scales: {
                            xAxes: [{ stacked: false, gridLines: { display: false, drawBorder: false } }],
                            yAxes: [{
                                stacked: false,
                                ticks: { beginAtZero: true, stepSize: 1, padding: 10 },
                                gridLines: { color: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2] }
                            }],
                        },
                        legend: { display: true, position: 'bottom', labels: { boxWidth: 12, padding: 15 } }
                    }
                });
            }

            var ctx2 = document.getElementById("dailyTotalChart");
            if (ctx2) {
                var dailyTotalChart = new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: [@for($i = 1; $i <= $daysInMonth; $i++) "{{ $i }}", @endfor],
                        datasets: [{
                            label: "Total Daily Mistakes",
                            backgroundColor: "#e74a3b",
                            hoverBackgroundColor: "#e02d1b",
                            borderColor: "#e74a3b",
                            data: {!! json_encode(array_values($dailyTotalData)) !!},
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
                        scales: {
                            xAxes: [{ gridLines: { display: false, drawBorder: false } }],
                            yAxes: [{
                                ticks: { beginAtZero: true, stepSize: 1, padding: 10 },
                                gridLines: { color: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2] }
                            }],
                        },
                        legend: { display: false }
                    }
                });
            }
        });
    </script>
@endsection

@section('style')
    <style>
        .daily-table thead tr:nth-child(1) th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8f9fc;
        }

        .daily-table thead tr:nth-child(2) th {
            position: sticky;
            top: 31px;
            z-index: 9;
            background-color: #f8f9fc;
        }

        .date-col {
            min-width: 30px;
            font-size: 0.8rem;
        }

        .bg-gray-100 {
            background-color: #f8f9fc !important;
        }
    </style>
@endsection