<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\WorkSchedule;
use App\Models\SpecialDate;

class WorkScheduleController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month);
        $daysInMonth = $date->daysInMonth;
        
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // Get overrides from WorkSchedule
        $overrides = WorkSchedule::whereBetween('tanggal', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->get()
            ->keyBy(function($item) {
                return Carbon::parse($item->tanggal)->format('Y-m-d');
            });

        // Get SpecialDates (from rifa) for the month
        // We will just load all special dates to memory (it's cached in model anyway)
        $specialDates = SpecialDate::loadData();

        $schedule = [];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $currentDate = $date->copy()->day($i);
            $dateStr = $currentDate->format('Y-m-d');
            $isWeekend = $currentDate->isWeekend();

            $isWorkday = SpecialDate::isWorkday($currentDate); // Panggil fungsi utama sebagai sumber kebenaran (DRY)
            $source = 'Default';

            if (isset($specialDates[$dateStr])) {
                $type = $specialDates[$dateStr];
                if (in_array($type, SpecialDate::HOLIDAY_TYPES) || $type === SpecialDate::FORCED_WORKDAY_TYPE) {
                    $source = 'Rifa (' . ucwords($type) . ')';
                }
            }

            $hasOverride = false;
            $overrideIsLibur = false;

            if (isset($overrides[$dateStr])) {
                $hasOverride = true;
                $overrideIsLibur = $overrides[$dateStr]->is_libur;
                $source = 'Manual Override';
            }

            $schedule[] = [
                'date' => $dateStr,
                'day_name' => $currentDate->locale('id')->isoFormat('dddd'),
                'is_workday' => $isWorkday,
                'source' => $source,
                'is_weekend' => $isWeekend,
                'has_override' => $hasOverride,
                'override_is_libur' => $overrideIsLibur,
            ];
        }

        return view('admins.work_schedules.index', compact('schedule', 'month'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'status' => 'required|in:workday,holiday,reset'
        ]);

        $tanggal = $request->tanggal;
        $status = $request->status;

        if ($status === 'reset') {
            WorkSchedule::where('tanggal', $tanggal)->delete();
            $msg = 'Pengaturan manual untuk tanggal ' . $tanggal . ' telah di-reset (kembali ke bawaan).';
        } else {
            $isLibur = ($status === 'holiday');
            WorkSchedule::updateOrCreate(
                ['tanggal' => $tanggal],
                ['is_libur' => $isLibur]
            );
            $statusText = $isLibur ? 'Libur' : 'Hari Kerja';
            $msg = 'Tanggal ' . $tanggal . ' berhasil diatur menjadi ' . $statusText . '.';
        }

        return redirect()->back()->with('success', $msg);
    }
}
