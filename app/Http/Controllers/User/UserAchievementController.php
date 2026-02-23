<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Request as RequestModel;
use App\Models\Record;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserAchievementController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month);
        $daysInMonth = $date->daysInMonth;
        
        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->get();
        
        $requestsData = [];
        $recordsData = [];
        
        foreach ($members as $member) {
            // Requests
            $userRequests = RequestModel::where('Id_User', $member->Id_Member)
                ->whereMonth('Day_Request', $date->month)
                ->whereYear('Day_Request', $date->year)
                ->get();
            
            $daysReq = array_fill(1, $daysInMonth, 0);
            foreach ($userRequests as $req) {
                $day = (int) Carbon::parse($req->Day_Request)->format('d');
                $daysReq[$day]++;
            }
            
            $requestsData[$member->Id_Member] = [
                'name' => $member->Name_Member,
                'total' => $userRequests->count(),
                'days' => $daysReq
            ];
            
            // Records
            $userRecords = Record::where('Id_User', $member->Id_Member)
                ->whereMonth('Day_Record', $date->month)
                ->whereYear('Day_Record', $date->year)
                ->get();
            
            $daysRec = array_fill(1, $daysInMonth, 0);
            foreach ($userRecords as $rec) {
                $day = (int) Carbon::parse($rec->Day_Record)->format('d');
                $daysRec[$day]++;
            }
            
            $recordsData[$member->Id_Member] = [
                'name' => $member->Name_Member,
                'total' => $userRecords->count(),
                'days' => $daysRec
            ];
        }

        // Sort by total descending
        uasort($requestsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        
        uasort($recordsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        
        return view('users.achievements.index', compact('requestsData', 'recordsData', 'month', 'daysInMonth'));
    }
}
