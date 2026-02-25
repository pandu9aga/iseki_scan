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
        // Hitung waktu 1 hari kerja lalu (tanpa Sabtu dan Minggu)
        $workdaysAgo = $now->copy();
        $daysCounted = 0;
        while ($daysCounted < 1) {
            $workdaysAgo->subDay();
            // Lewati Sabtu (6) dan Minggu (0)
            if (!in_array($workdaysAgo->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                $daysCounted++;
            }
        }

        $requests = RequestModel::with('member', 'record')
            ->where('Status_Request', '!=', 'Done')
            ->where(function ($query) use ($workdaysAgo) {
                $query->where(function ($q) use ($workdaysAgo) {
                    $q->whereNotNull('Ready_Request')
                    ->where('Ready_Request', '<', $workdaysAgo);
                });
                // ->orWhere(function ($q) use ($workdaysAgo) {
                //     $q->whereNotNull('Shipping_Request')
                //     ->where('Shipping_Request', '<', $workdaysAgo);
                // })
                // ->orWhere(function ($q) use ($workdaysAgo) {
                //     $q->whereNotNull('Production_Area_Request')
                //     ->where('Production_Area_Request', '<', $workdaysAgo);
                // })
                // ->orWhere(function ($q) use ($workdaysAgo) {
                //     $q->whereNotNull('Design_Changes_Request')
                //     ->where('Design_Changes_Request', '<', $workdaysAgo);
                // });
            })
            ->orderBy('Day_Request', 'desc')
            ->get();

        // Ambil semua request yang belum ada status sama sekali
        $mcMiss = RequestModel::with('member', 'record')
            ->where('Status_Request', '!=', 'Done')
            ->whereNull('Ready_Request')
            ->whereNull('Shipping_Request')
            ->whereNull('Production_Area_Request')
            ->whereNull('Design_Changes_Request')
            ->get();

        $missingRequests = $mcMiss->filter(function ($mcMiss) use ($now) {
            $requestTime = Carbon::parse($mcMiss->Day_Request . ' ' . $mcMiss->Time_Request);

            // Jika request di masa depan, skip
            if ($requestTime->gt($now)) {
                return false;
            }

            $current = $requestTime->copy();
            $workingHours = 0;

            // Loop per jam sampai mencapai now
            while ($current->lt($now)) {
                // Lewati Sabtu (6) dan Minggu (0)
                if (!in_array($current->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                    $workingHours++;
                }
                $current->addHour();
            }

            // Jika sudah lewat 24 jam kerja → missing
            return $workingHours > 24;
        })->count();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequests = $requests->count();

        return view('admins.index', compact('totalRecords', 'correct', 'incorrect', 'maxProgress', 'totalRequests', 'missingRequests'));
    }    
}
