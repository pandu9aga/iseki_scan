<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Record;

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

        return view('admins.index', compact('totalRecords', 'correct', 'incorrect', 'maxProgress'));
    }    
}
