<?php

namespace App\Http\Controllers\Qc;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\Member;
use App\Models\Rack;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WithdrawalController extends Controller
{
    /**
     * Show withdrawal table with all records
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

        return view('qcs.withdrawal.index', compact('withdrawals'));
    }

    /**
     * QC creates a new pengajuan (submission)
     * Input: Name_Withdrawal (PIC name), Code_Item_Withdrawal (part code)
     */
    public function store(Request $request)
    {
        $request->validate([
            'Name_Withdrawal' => 'required|string',
            'Code_Item_Withdrawal' => 'required|string',
        ]);

        // Verify that Code_Item_Withdrawal exists in racks table
        $rack = Rack::where('Code_Item_Rack', $request->Code_Item_Withdrawal)->first();
        if (!$rack) {
            return back()->withErrors(['Code_Item_Withdrawal' => 'Kode Part tidak ditemukan di data rak.'])->withInput();
        }

        Withdrawal::create([
            'Name_Withdrawal' => $request->Name_Withdrawal,
            'Code_Item_Withdrawal' => $request->Code_Item_Withdrawal,
            'Date_Withdrawal' => Carbon::now(),
        ]);

        return back()->with('success', 'Pengajuan withdrawal berhasil dibuat.');
    }

    /**
     * DST clicks OK - sets NIK_Withdrawal (who prepared it)
     * Input: NIK (DST member NIK)
     */
    public function oke($id, Request $request)
    {
        $request->validate([
            'NIK_Withdrawal' => 'required|integer',
        ]);

        // Verify NIK exists in members table
        $member = Member::where('NIK_Member', $request->NIK_Withdrawal)->first();
        if (!$member) {
            return back()->withErrors(['NIK_Withdrawal' => 'NIK tidak ditemukan di data member.']);
        }

        $withdrawal = Withdrawal::findOrFail($id);
        $withdrawal->update([
            'Oke_Withdrawal' => true,
            'NIK_Withdrawal' => $request->NIK_Withdrawal,
        ]);

        return back()->with('success', 'Withdrawal telah disiapkan oleh ' . $member->Name_Member);
    }

    /**
     * QC clicks "Diterima" - marks item as received
     */
    public function receiving($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        $withdrawal->update([
            'Oke_Receiving' => true,
            'Date_Receiving' => Carbon::now(),
        ]);

        return back()->with('success', 'Barang telah diterima.');
    }

    /**
     * QC clicks "Selesai" - marks QC process as finished
     */
    public function finish($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        $withdrawal->update([
            'Finish_Receiving' => true,
            'Date_Finish_Receiving' => Carbon::now(),
        ]);

        return back()->with('success', 'QC telah selesai.');
    }

    /**
     * DST returns item to rack
     * Input: NIK_Return (DST member NIK), Code_Rack_Return (scanned barcode)
     * Validation: Code_Rack_Return must match a rack that has Code_Item_Rack == Code_Item_Withdrawal
     */
    public function returnRack($id, Request $request)
    {
        $request->validate([
            'NIK_Return' => 'required|integer',
            'Code_Rack_Return' => 'required|string',
        ]);

        $withdrawal = Withdrawal::findOrFail($id);

        // Verify NIK exists in members table
        $member = Member::where('NIK_Member', $request->NIK_Return)->first();
        if (!$member) {
            return back()->withErrors(['NIK_Return' => 'NIK tidak ditemukan di data member.']);
        }

        // Verify barcode matches: Code_Rack_Return must correspond to a rack
        // whose Code_Item_Rack matches the original Code_Item_Withdrawal
        $rack = Rack::where('Code_Rack', $request->Code_Rack_Return)->first();
        if (!$rack) {
            return back()->withErrors(['Code_Rack_Return' => 'Kode rak tidak ditemukan.']);
        }

        if ($rack->Code_Item_Rack !== $withdrawal->Code_Item_Withdrawal) {
            return back()->withErrors(['Code_Rack_Return' => 'Kode rak tidak sesuai dengan kode part pengajuan! Part: ' . $withdrawal->Code_Item_Withdrawal . ', Rak: ' . $rack->Code_Item_Rack]);
        }

        $withdrawal->update([
            'NIK_Return' => $request->NIK_Return,
            'Code_Rack_Return' => $request->Code_Rack_Return,
            'Date_Return' => Carbon::now(),
        ]);

        return back()->with('success', 'Barang telah dikembalikan ke rak oleh ' . $member->Name_Member);
    }
}
