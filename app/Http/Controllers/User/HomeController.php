<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Record;

class HomeController extends Controller
{
    public function index(){
        $date = Carbon::today();
        $records = Record::whereDate('Day_Record', $date)->where('Id_User', session('Id_User'))->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRecords = $records->count();
        
        $correct = $records->filter(function ($record) {
            return $record->Correctness_Record == 1;
        })->count();
        $incorrect = $records->count() - $correct;

        return view('users.index', compact('records','totalRecords', 'correct', 'incorrect','formattedDate','date'));
    }    
}
