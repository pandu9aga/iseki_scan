<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Request as RequestModel;
use App\Models\Record;
use App\Models\BranchRecord;
use Carbon\Carbon;

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
        $branchRecordsData = [];
        
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
            
            // Records Biasa
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

            // Branch Records
            $userBranchRecords = BranchRecord::where('Id_User', $member->Id_Member)
                ->whereMonth('Day_Branch_Record', $date->month)
                ->whereYear('Day_Branch_Record', $date->year)
                ->get();
            
            $daysBranch = array_fill(1, $daysInMonth, 0);
            foreach ($userBranchRecords as $br) {
                $day = (int) Carbon::parse($br->Day_Branch_Record)->format('d');
                $daysBranch[$day]++;
            }
            
            $branchRecordsData[$member->Id_Member] = [
                'name' => $member->Name_Member,
                'total' => $userBranchRecords->count(),
                'days' => $daysBranch
            ];
        }

        // Sort requests by total descending
        uasort($requestsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        
        // Sort records by combined (Record + BranchRecord) total descending
        uasort($recordsData, function ($a, $b) use ($branchRecordsData) {
            $memberIdA = array_search($a, $GLOBALS['temp_records_ref'] ?? []);
            $totalA = $a['total'] + ($branchRecordsData[$memberIdA]['total'] ?? 0);
            $totalB = $b['total'] + ($branchRecordsData[$memberIdA]['total'] ?? 0);
            return $b['total'] <=> $a['total'];
        });

        return view('users.achievements.index', compact('requestsData', 'recordsData', 'branchRecordsData', 'month', 'daysInMonth'));
    }
}
