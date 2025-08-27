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
        $requests = RequestModel::with('member', 'record')
            ->where('Status_Request', '!=', 'Done')
            ->whereRaw("TIMESTAMP(Day_Request, Time_Request) < ?", [Carbon::now()->subHours(48)])
            ->orderBy('Day_Request', 'desc')
            ->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequests = $requests->count();

        return view('admins.index', compact('totalRecords', 'correct', 'incorrect', 'maxProgress', 'totalRequests'));
    }    
}
