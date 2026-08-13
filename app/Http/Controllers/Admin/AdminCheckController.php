<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Check;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminCheckController extends Controller
{
    /**
     * Show check list page with DataTable
     */
    public function index(Request $request)
    {
        $query = Check::whereNotNull('Status_Check');

        // Filter by input date
        if ($date = $request->input('date')) {
            $query->whereDate('Time_Check', $date);
        }

        // Filter by target check date
        if ($targetDate = $request->input('target_date')) {
            $query->whereDate('Status_Check', $targetDate);
        }

        // Filter status relatif dari HARI INI
        if ($status = $request->input('status')) {
            if ($status === 'today') {
                $query->whereDate('Status_Check', Carbon::today());
            } elseif (is_numeric($status)) {
                $query->whereDate('Status_Check', Carbon::today()->addDays((int)$status));
            }
        }

        // Filter by checker (Id_User)
        if ($checker = $request->input('checker')) {
            $query->where('Id_User', $checker);
        }

        $checks = $query->orderBy('Id_Checks', 'desc')->get();

        // Ambil ID hanya dari data yang sudah terfilter untuk map nama di tabel
        $memberIds = $checks->filter(fn($c) => !$c->Is_User)->pluck('Id_User')->filter()->unique();
        $adminIds  = $checks->filter(fn($c) => $c->Is_User)->pluck('Id_User')->filter()->unique();

        $membersMap = $memberIds->isNotEmpty()
            ? \App\Models\Member::whereIn('Id_Member', $memberIds)->pluck('Name_Member', 'Id_Member')->toArray()
            : [];

        $usersMap = $adminIds->isNotEmpty()
            ? \App\Models\User::whereIn('Id_User', $adminIds)->pluck('Username_User', 'Id_User')->toArray()
            : [];

        // checkerList untuk dropdown - ambil dari semua data TANPA filter agar dropdown konsisten
        $allChecks = Check::select('Id_User', 'Is_User')->distinct()->get();
        $allMemberIds = $allChecks->filter(fn($c) => !$c->Is_User)->pluck('Id_User')->filter()->unique();
        $allAdminIds  = $allChecks->filter(fn($c) => $c->Is_User)->pluck('Id_User')->filter()->unique();

        $checkerList = [];
        if ($allMemberIds->isNotEmpty()) {
            $members = \App\Models\Member::whereIn('Id_Member', $allMemberIds)->pluck('Name_Member', 'Id_Member');
            foreach ($members as $id => $name) {
                $checkerList[$id] = $name;
            }
        }
        if ($allAdminIds->isNotEmpty()) {
            $admins = \App\Models\User::whereIn('Id_User', $allAdminIds)->pluck('Username_User', 'Id_User');
            foreach ($admins as $id => $name) {
                $checkerList[$id] = $name . ' (Admin)';
            }
        }
        asort($checkerList);

        // Rack map hanya dari data terfilter
        $codes = $checks->pluck('Code_Rack')->filter()->unique();
        $racksMap = $codes->isNotEmpty()
            ? \App\Models\Rack::whereIn('Code_Rack', $codes)->pluck('Name_Item_Rack', 'Code_Rack')->toArray()
            : [];

        foreach ($checks as $c) {
            $c->checker_name = $c->Is_User
                ? (($usersMap[$c->Id_User] ?? '-') . ' (Admin)')
                : ($membersMap[$c->Id_User] ?? '-');
            $c->rack_name = $racksMap[$c->Code_Rack] ?? '-';
        }

        return view('admins.checks.index', compact('checks', 'checkerList'));
    }

    /**
     * Store a check from the admin requesting page
     */
    public function store(Request $request)
    {
        $request->validate([
            'Code_Rack' => 'required|string',
            'Code_Item' => 'required|string',
            'Status_Check' => 'required|integer|min:1',
        ]);

        $now = Carbon::now();
        $statusDays = intval($request->input('Status_Check'));
        $targetDate = $now->copy()->addDays($statusDays)->format('Y-m-d');

        // Jika ada check_id, tandai check lama sebagai selesai
        if ($request->has('check_id') && !empty($request->input('check_id'))) {
            $oldCheck = Check::find($request->input('check_id'));
            if ($oldCheck) {
                $oldCheck->Status_Check = null;
                $oldCheck->save();
            }
        }

        Check::create([
            'Time_Check' => $now,
            'Code_Rack' => $request->input('Code_Rack'),
            'Code_Item_Rack' => substr($request->input('Code_Item'), 0, 12),
            'Id_User' => session('Id_User'),
            'Status_Check' => $targetDate,
            'Is_User' => 1,
        ]);

        $label = $request->input('Status_Check') . ' Hari Ke Depan';

        return redirect()->route('admin.requesting', [
            'area' => $request->input('Area_Request'),
            'code_rack' => $request->input('Code_Rack'),
            'code_item' => $request->input('Code_Item'),
        ])->with('success', "Check {$label} berhasil disimpan.");
    }

    /**
     * Mark a check as done (sets Status_Check to NULL)
     */
    public function markAsDone(Request $request, $id)
    {
        $check = Check::findOrFail($id);
        $check->Status_Check = null;
        $check->save();

        $filterParams = array_filter($request->only(['date', 'target_date', 'status', 'checker']));
        return redirect()->route('admin.check', $filterParams)->with('success', 'Check berhasil ditandai sebagai Selesai.');
    }
}
