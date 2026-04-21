<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Check;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserCheckController extends Controller
{
    /**
     * Show check list page with DataTable
     */
    public function index(Request $request)
    {
        $query = Check::query();

        // Filter by date (optional — empty = show all)
        $date = $request->input('date');
        if ($date) {
            $query->whereDate('Time_Check', $date);
        }

        // Filter by status
        $status = $request->input('status');
        if ($status) {
            $query->where('Status_Check', $status);
        }

        // Filter by checker (Id_User)
        $checker = $request->input('checker');
        if ($checker) {
            $query->where('Id_User', $checker);
        }

        $checks = $query->orderBy('Id_Checks', 'desc')->get();

        // Build member & user maps for all checks
        $allMemberIds = Check::select('Id_User')->where('Is_User', 0)->orWhereNull('Is_User')->distinct()->pluck('Id_User')->filter();
        $allAdminIds = Check::select('Id_User')->where('Is_User', 1)->distinct()->pluck('Id_User')->filter();

        $membersMap = [];
        if ($allMemberIds->isNotEmpty()) {
            $members = \App\Models\Member::whereIn('Id_Member', $allMemberIds)->get()->keyBy('Id_Member');
            foreach ($members as $id => $member) {
                $membersMap[$id] = $member->Name_Member;
            }
        }

        $usersMap = [];
        if ($allAdminIds->isNotEmpty()) {
            $users = \App\Models\User::whereIn('Id_User', $allAdminIds)->get()->keyBy('Id_User');
            foreach ($users as $id => $user) {
                $usersMap[$id] = $user->Username_User;
            }
        }

        // Build checker list for filter dropdown
        $checkerList = [];
        foreach ($membersMap as $id => $name) {
            $checkerList[$id] = $name;
        }
        foreach ($usersMap as $id => $name) {
            $checkerList[$id] = $name . ' (Admin)';
        }
        asort($checkerList);

        // Enrich rack info
        $codes = $checks->pluck('Code_Rack')->filter()->unique();
        $racksMap = [];
        if ($codes->isNotEmpty()) {
            $racks = \App\Models\Rack::whereIn('Code_Rack', $codes)->get()->keyBy('Code_Rack');
            foreach ($racks as $code => $rack) {
                $racksMap[$code] = $rack->Name_Item_Rack;
            }
        }

        foreach ($checks as $c) {
            if ($c->Is_User && isset($usersMap[$c->Id_User])) {
                $c->checker_name = $usersMap[$c->Id_User] . ' (Admin)';
            } elseif (isset($membersMap[$c->Id_User])) {
                $c->checker_name = $membersMap[$c->Id_User];
            } else {
                $c->checker_name = '-';
            }

            $c->rack_name = $racksMap[$c->Code_Rack] ?? '-';
        }

        $dateForInput = $date ?? '';

        return view('users.checks.index', compact('checks', 'dateForInput', 'checkerList'));
    }

    /**
     * Store a check from the request scan page
     */
    public function store(Request $request)
    {
        $request->validate([
            'Code_Rack' => 'required|string',
            'Code_Item' => 'required|string',
            'Status_Check' => 'required|in:1,2',
        ]);

        Check::create([
            'Time_Check' => Carbon::now(),
            'Code_Rack' => $request->input('Code_Rack'),
            'Code_Item_Rack' => substr($request->input('Code_Item'), 0, 12),
            'Id_User' => session('Id_Member'),
            'Status_Check' => $request->input('Status_Check'),
            'Is_User' => 0,
        ]);

        $label = $request->input('Status_Check') == 1 ? 'Mid' : 'Lot';

        return redirect()->back()->with('success', "Check {$label} berhasil disimpan.");
    }
}
