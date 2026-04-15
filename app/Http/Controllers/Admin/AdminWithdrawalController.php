<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\Member;
use Illuminate\Http\Request;

class AdminWithdrawalController extends Controller
{
    /**
     * Show withdrawal table with all records (Read-Only)
     */
    public function index()
    {
        $withdrawals = Withdrawal::orderBy('Id_Withdrawal', 'desc')->get();

        // Enrich with member names for NIK_Withdrawal and NIK_Return
        foreach ($withdrawals as $w) {
            if ($w->NIK_Withdrawal) {
                $member = Member::where('NIK_Member', $w->NIK_Withdrawal)->first();
                $w->name_disiapkan = $member ? $member->Name_Member : '-';
            }
            if ($w->NIK_Return) {
                $member = Member::where('NIK_Member', $w->NIK_Return)->first();
                $w->name_return = $member ? $member->Name_Member : '-';
            }
        }

        return view('admin.withdrawal.index', compact('withdrawals'));
    }
}
