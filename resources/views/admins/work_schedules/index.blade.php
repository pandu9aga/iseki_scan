@extends('layouts.main')

@section('style')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Work Schedule</h1>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Jadwal Hari Kerja: {{ \Carbon\Carbon::parse($month)->locale('id')->isoFormat('MMMM YYYY') }}</h6>
            <form action="{{ route('admin.work_schedule') }}" method="GET" class="form-inline">
                <input type="month" name="month" class="form-control form-control-sm mr-2" value="{{ $month }}">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Hari</th>
                            <th>Status Hari</th>
                            <th>Sumber Info</th>
                            <th>Aksi Manual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($schedule as $day)
                            @php
                                $dateObj = \Carbon\Carbon::parse($day['date']);
                                $isWorkday = $day['is_workday'];
                                $badgeClass = $isWorkday ? 'badge-success' : 'badge-danger';
                                $badgeText = $isWorkday ? 'Hari Kerja' : 'Libur';
                            @endphp
                            <tr>
                                <td>{{ $dateObj->format('d-m-Y') }}</td>
                                <td>{{ $day['day_name'] }}</td>
                                <td>
                                    <span class="badge {{ $badgeClass }} px-2 py-1" style="font-size: 0.85rem;">{{ $badgeText }}</span>
                                </td>
                                <td>
                                    @if($day['has_override'])
                                        <span class="text-primary font-weight-bold"><i class="fas fa-exclamation-circle"></i> {{ $day['source'] }}</span>
                                    @else
                                        <span class="text-secondary">{{ $day['source'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <form action="{{ route('admin.work_schedule.update') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="tanggal" value="{{ $day['date'] }}">
                                            @if(!$isWorkday)
                                                <input type="hidden" name="status" value="workday">
                                                <button type="submit" class="btn btn-sm btn-success" title="Jadikan Hari Kerja">
                                                    <i class="fas fa-calendar-check"></i> Set Masuk
                                                </button>
                                            @else
                                                <input type="hidden" name="status" value="holiday">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Jadikan Hari Libur">
                                                    <i class="fas fa-calendar-times"></i> Set Libur
                                                </button>
                                            @endif
                                        </form>

                                        @if($day['has_override'])
                                            <form action="{{ route('admin.work_schedule.update') }}" method="POST" class="d-inline ml-1">
                                                @csrf
                                                <input type="hidden" name="tanggal" value="{{ $day['date'] }}">
                                                <input type="hidden" name="status" value="reset">
                                                <button type="submit" class="btn btn-sm btn-warning" title="Reset ke Default">
                                                    <i class="fas fa-undo"></i> Reset
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 alert alert-info">
                <strong><i class="fas fa-info-circle"></i> Keterangan:</strong><br>
                <ul>
                    <li><strong>Status Hari</strong> menentukan apakah request yang diinput hari tersebut setelah jam 15:30 diproses di hari yang sama atau keesokan harinya.</li>
                    <li><strong>Aksi Manual</strong> akan meng-override (menimpa) data bawaan sistem maupun data kalender perusahaan (rifa). Ini berguna untuk menetapkan "Libur Masuk" saat akhir pekan, atau meliburkan hari kerja biasa.</li>
                    <li>Gunakan tombol <strong>Reset</strong> untuk menghapus override manual dan mengembalikan status tanggal sesuai bawaan sistem/rifa.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script>
    $(document).ready(function() {
        if (!$.fn.DataTable.isDataTable('#dataTable')) {
            $('#dataTable').DataTable({
                "pageLength": 100,
                "ordering": false // matikan sort otomatis biar urut tanggal
            });
        } else {
            var t = $('#dataTable').DataTable();
            t.page.len(100).draw();
        }
    });
</script>
@endsection
