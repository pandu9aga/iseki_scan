<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\Member;
use App\Models\Rack;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserWithdrawalController extends Controller
{
    /**
     * Show withdrawal page for member (DST)
     * Shows items that need OK (disiapkan) and items that need return (masuk rak)
     */
    public function index()
    {
        // Pending OK: withdrawals that haven't been prepared yet
        $pendingOke = Withdrawal::whereNull('Oke_Withdrawal')
            ->orWhere('Oke_Withdrawal', false)
            ->orderBy('Date_Withdrawal', 'desc')
            ->get();

        // Pending Return: withdrawals that are finished QC but not returned to rack
        $pendingReturn = Withdrawal::where('Finish_Receiving', true)
            ->whereNull('Date_Return')
            ->orderBy('Date_Finish_Receiving', 'desc')
            ->get();

        // History: completed withdrawals by this member
        $nikMember = session('NIK_Member');
        $history = Withdrawal::where(function($q) use ($nikMember) {
                $q->where('NIK_Withdrawal', $nikMember)
                  ->orWhere('NIK_Return', $nikMember);
            })
            ->orderBy('Id_Withdrawal', 'desc')
            ->limit(50)
            ->get();

        // Enrich with member names
        foreach ([$pendingOke, $pendingReturn, $history] as $collection) {
            foreach ($collection as $w) {
                if ($w->NIK_Withdrawal) {
                    $member = Member::where('NIK_Member', $w->NIK_Withdrawal)->first();
                    $w->name_disiapkan = $member ? $member->Name_Member : '-';
                }
                if ($w->NIK_Return) {
                    $member = Member::where('NIK_Member', $w->NIK_Return)->first();
                    $w->name_return = $member ? $member->Name_Member : '-';
                }
            }
        }

        return view('users.withdrawal.index', compact('pendingOke', 'pendingReturn', 'history'));
    }

    /**
     * DST clicks OK - confirms they will prepare the item
     * Uses the member's own NIK from session
     */
    public function oke($id, Request $request)
    {
        $nikMember = session('NIK_Member');
        if (!$nikMember) {
            return back()->withErrors(['error' => 'Session expired. Silakan login ulang.']);
        }

        $withdrawal = Withdrawal::findOrFail($id);
        $withdrawal->update([
            'Oke_Withdrawal' => true,
            'NIK_Withdrawal' => $nikMember,
        ]);

        $nameMember = session('Name_Member', 'Member');
        return back()->with('success', 'Withdrawal disiapkan oleh ' . $nameMember);
    }

    /**
     * DST returns item to rack
     * Uses the member's own NIK from session + scanned barcode
     */
    public function returnRack($id, Request $request)
    {
        $request->validate([
            'Code_Rack_Return' => 'required|string',
        ]);

        $nikMember = session('NIK_Member');
        if (!$nikMember) {
            return back()->withErrors(['error' => 'Session expired. Silakan login ulang.']);
        }

        $withdrawal = Withdrawal::findOrFail($id);

        // Verify barcode matches: Code_Rack must correspond to the original Code_Item_Withdrawal
        $rack = Rack::where('Code_Rack', $request->Code_Rack_Return)->first();
        if (!$rack) {
            return back()->withErrors(['Code_Rack_Return' => 'Kode rak tidak ditemukan.']);
        }

        if ($rack->Code_Item_Rack !== $withdrawal->Code_Item_Withdrawal) {
            return back()->withErrors(['Code_Rack_Return' => 'Kode rak tidak sesuai dengan kode part pengajuan! Part: ' . $withdrawal->Code_Item_Withdrawal . ', Rak: ' . $rack->Code_Item_Rack]);
        }

        $withdrawal->update([
            'NIK_Return' => $nikMember,
            'Code_Rack_Return' => $request->Code_Rack_Return,
            'Date_Return' => Carbon::now(),
        ]);

        $nameMember = session('Name_Member', 'Member');
        return back()->with('success', 'Barang dikembalikan ke rak oleh ' . $nameMember);
    }
}
