@extends('layouts.main')

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Mistake Report (Daily)</h1>
            <div>
                <form action="{{ route('mistake') }}" method="GET" class="form-inline d-inline">
                    <input type="month" name="month" class="form-control mr-1" value="{{ $month }}">
                    <button type="submit" class="btn btn-primary mr-1">Submit</button>
                </form>
                <a href="{{ route('mistake.export', ['month' => $month]) }}" class="btn btn-success shadow-sm mr-1">
                    <i class="fas fa-file-excel fa-sm text-white-50"></i> Export Excel
                </a>
                <a href="{{ route('mistake.add') }}" class="btn btn-danger shadow-sm">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Add Mistake
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

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
                                                    <a
                                                        href="{{ route('mistake.detail', ['member_id' => $memberId, 'category' => $cat, 'month' => $month]) }}">
                                                        {{ $data['name'] }}
                                                    </a>
                                                </td>
                                                <td class="text-center bg-gray-100 font-weight-bold">
                                                    <a href="{{ route('mistake.detail', ['member_id' => $memberId, 'category' => $cat, 'month' => $month]) }}"
                                                        class="text-dark">
                                                        {{ $data['total'] }}
                                                    </a>
                                                </td>
                                                @for($i = 1; $i <= $daysInMonth; $i++)
                                                    <td
                                                        class="text-center {{ $data['days'][$i] > 0 ? 'bg-danger text-white' : 'text-gray-300' }}">
                                                        @if($data['days'][$i] > 0)
                                                            <a href="{{ route('mistake.detail', ['member_id' => $memberId, 'category' => $cat, 'month' => $month, 'day' => $i]) }}"
                                                                class="text-white font-weight-bold d-block">
                                                                {{ $data['days'][$i] }}
                                                            </a>
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
    <script src="{{ asset('js/chartjs-plugin-datalabels.js') }}"></script>
    <script>
        $(document).ready(function () {
            // Colors for members
            const colors = [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#858796', '#5a5c69', '#6610f2', '#6f42c1', '#e83e8c',
                '#fd7e14', '#20c997', '#007bff', '#6c757d', '#28a745'
            ];

            // Mistake Chart Implementation
            var ctx = document.getElementById("mistakeChart");
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
                    layout: {
                        padding: {
                            left: 10,
                            right: 25,
                            top: 25,
                            bottom: 0
                        }
                    },
                    scales: {
                        xAxes: [{
                            stacked: false,
                            gridLines: {
                                display: false,
                                drawBorder: false
                            },
                        }],
                        yAxes: [{
                            stacked: false,
                            ticks: {
                                beginAtZero: true,
                                stepSize: 1,
                                padding: 10,
                            },
                            gridLines: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        }],
                    },
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    },
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        titleMarginBottom: 10,
                        titleFontColor: '#6e707e',
                        titleFontSize: 14,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: true,
                        intersect: false,
                        mode: 'index',
                        caretPadding: 10,
                    },
                    plugins: {
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            color: '#858796',
                            font: {
                                weight: 'bold'
                            },
                            formatter: function (value, context) {
                                return value;
                            }
                        }
                    }
                }
            });

            // Daily Total Bar Chart Implementation
            var ctx2 = document.getElementById("dailyTotalChart");
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
                    layout: {
                        padding: {
                            left: 10,
                            right: 25,
                            top: 25,
                            bottom: 0
                        }
                    },
                    scales: {
                        xAxes: [{
                            gridLines: {
                                display: false,
                                drawBorder: false
                            },
                        }],
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                stepSize: 1,
                                padding: 10,
                            },
                            gridLines: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        }],
                    },
                    legend: {
                        display: false
                    },
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        titleMarginBottom: 10,
                        titleFontColor: '#6e707e',
                        titleFontSize: 14,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        intersect: false,
                        mode: 'index',
                        caretPadding: 10,
                    },
                    plugins: {
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            color: '#fff',
                            backgroundColor: '#e74a3b',
                            borderRadius: 4,
                            font: {
                                weight: 'bold'
                            },
                            formatter: function (value, context) {
                                return value;
                            }
                        }
                    }
                }
            });
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