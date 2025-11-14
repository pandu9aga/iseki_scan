<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Record;
use App\Models\Request as RequestModel;

class AdminController extends Controller
{
    public function index(){
        $today = Carbon::today();
        $records = Record::whereDate('Day_Record', $today)->get();
        $totalRecords = $records->count();
        
        $correct = $records->filter(function ($record) {
            return $record->Correctness_Record == 1;
        })->count();
        $incorrect = $records->count() - $correct;

        $maxValue = max($correct, $incorrect);
        $maxProgress = pow(10, ceil(log10($maxValue)));

        $date = Carbon::today()->format('Y-m-d');

        $now = Carbon::now();
        // Hitung waktu 2 hari kerja lalu (tanpa Sabtu dan Minggu)
        $workdaysAgo = $now->copy();
        $daysCounted = 0;
        while ($daysCounted < 2) {
            $workdaysAgo->subDay();
            // Lewati Sabtu (6) dan Minggu (0)
            if (!in_array($workdaysAgo->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                $daysCounted++;
            }
        }

        $requests = RequestModel::with('member', 'record')
            ->where('Status_Request', '!=', 'Done')
            ->whereRaw("TIMESTAMP(Day_Request, Time_Request) < ?", [$workdaysAgo])
            ->orderBy('Day_Request', 'desc')
            ->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequests = $requests->count();

        return view('admins.index', compact('totalRecords', 'correct', 'incorrect', 'maxProgress', 'totalRequests'));
    }    
}
