<?php

namespace App\Http\Controllers\Qc;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\Member;
use App\Models\Rack;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class WithdrawalController extends Controller
{
    /**
     * Show withdrawal table with all records
     */
    public function index(Request $request)
    {
        $query = Withdrawal::query();

        // Filter Status
        $status = $request->input('status', 'all');
        if ($status == 'unfinished') {
            $query->whereNull('Date_Return');
        } elseif ($status == 'finished') {
            $query->whereNotNull('Date_Return');
        }

        // Filter Date
        $date = $request->input('date');
        $month = $request->input('month');

        if ($date) {
            $query->whereDate('Date_Withdrawal', $date);
        } elseif ($month) {
            $parsedMonth = Carbon::parse($month . '-01');
            $query->whereMonth('Date_Withdrawal', $parsedMonth->format('m'))
                  ->whereYear('Date_Withdrawal', $parsedMonth->format('Y'));
        } else {
            $query->whereMonth('Date_Withdrawal', Carbon::now()->format('m'))
                  ->whereYear('Date_Withdrawal', Carbon::now()->format('Y'));
        }

        $withdrawals = $query->orderBy('Id_Withdrawal', 'desc')->get();

        // Optimasi: Kumpulkan NIK unik
        $niks = $withdrawals->pluck('NIK_Withdrawal')
            ->merge($withdrawals->pluck('NIK_Return'))
            ->filter()
            ->unique();

        $membersMap = [];
        $usersMap = [];
        if ($niks->isNotEmpty()) {
            $members = Member::whereIn('NIK_Member', $niks)->get()->keyBy('NIK_Member');
            foreach ($members as $nik => $member) {
                $membersMap[$nik] = $member->Name_Member;
            }
            $users = \App\Models\User::whereIn('Id_User', $niks)->get()->keyBy('Id_User');
            foreach ($users as $id => $user) {
                $usersMap[$id] = $user->Username_User;
            }
        }

        // Optimasi: Kumpulkan Code_Item unik
        $codes = $withdrawals->pluck('Code_Item_Withdrawal')->filter()->unique();
        $racksMap = [];
        if ($codes->isNotEmpty()) {
            $racks = Rack::whereIn('Code_Item_Rack', $codes)->get()->keyBy('Code_Item_Rack');
            foreach ($racks as $code => $rack) {
                $racksMap[$code] = [
                    'name' => $rack->Name_Item_Rack,
                    'no' => $rack->Code_Rack
                ];
            }
        }

        // Enrich with member names and rack info
        $total = $withdrawals->count();
        foreach ($withdrawals as $index => $w) {
            $w->no_urut = $total - $index;
            if ($w->NIK_Withdrawal) {
                if ($w->Is_User && isset($usersMap[$w->NIK_Withdrawal])) {
                    $w->name_disiapkan = $usersMap[$w->NIK_Withdrawal] . ' (Admin)';
                } elseif (isset($membersMap[$w->NIK_Withdrawal])) {
                    $w->name_disiapkan = $membersMap[$w->NIK_Withdrawal];
                } elseif (isset($usersMap[$w->NIK_Withdrawal])) {
                    $w->name_disiapkan = $usersMap[$w->NIK_Withdrawal] . ' (Admin)';
                } else {
                    $w->name_disiapkan = '-';
                }
            } else {
                $w->name_disiapkan = '-';
            }
            
            if ($w->NIK_Return) {
                if ($w->Is_User && isset($usersMap[$w->NIK_Return])) {
                    $w->name_return = $usersMap[$w->NIK_Return] . ' (Admin)';
                } elseif (isset($membersMap[$w->NIK_Return])) {
                    $w->name_return = $membersMap[$w->NIK_Return];
                } elseif (isset($usersMap[$w->NIK_Return])) {
                    $w->name_return = $usersMap[$w->NIK_Return] . ' (Admin)';
                } else {
                    $w->name_return = '-';
                }
            } else {
                $w->name_return = '-';
            }
            // Rack info based on Code_Item_Withdrawal
            $rackInfo = $racksMap[$w->Code_Item_Withdrawal] ?? null;
            $w->rack_name = $rackInfo ? $rackInfo['name'] : '-';
            $w->rack_no   = $rackInfo ? $rackInfo['no'] : '-';
        }

        return view('qcs.withdrawal.index', compact('withdrawals'));
    }

    /**
     * AJAX Search for racks to autocomplete "Kode Part"
     */
    public function searchRack(Request $request)
    {
        $query = $request->input('query');
        
        if (!$query) {
            return response()->json([]);
        }

        $racks = Rack::where('Code_Item_Rack', 'like', '%' . $query . '%')
            ->orWhere('Code_Rack', 'like', '%' . $query . '%')
            ->orWhere('Name_Item_Rack', 'like', '%' . $query . '%')
            ->select('Code_Item_Rack', 'Name_Item_Rack', 'Code_Rack')
            ->groupBy('Code_Item_Rack', 'Name_Item_Rack', 'Code_Rack')
            ->limit(10)
            ->get();

        $results = $racks->map(function($rack) {
            return [
                'item_code' => $rack->Code_Item_Rack,
                'part_name' => $rack->Name_Item_Rack,
                'rack_no'   => $rack->Code_Rack
            ];
        });

        return response()->json($results);
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
     * Delete withdrawal data
     * Only if not yet 'OK'ed by DST
     */
    public function destroy($id)
    {
        try {
            $withdrawal = Withdrawal::findOrFail($id);

            // Guard: Cannot delete if already OKed by DST
            if ($withdrawal->Oke_Withdrawal) {
                return back()->withErrors(['error' => 'Data tidak dapat dihapus karena sudah di-OKE oleh pihak DST.']);
            }

            $withdrawal->delete();
            return back()->with('success', 'Data withdrawal berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data: ' . $e->getMessage()]);
        }
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
     * Guard: Requires Arrive_Qc to be true (DST must confirm arrival at QC first)
     */
    public function receiving($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        // Guard: Cegah double-click
        if ($withdrawal->Oke_Receiving) {
            return back()->withErrors(['error' => 'Barang sudah diterima sebelumnya.']);
        }

        // Guard: DST harus sudah konfirmasi sampai di QC
        if (!$withdrawal->Arrive_Qc) {
            return back()->withErrors(['error' => 'Barang belum dikonfirmasi sampai di QC oleh DST.']);
        }

        $withdrawal->update([
            'Oke_Receiving' => true,
            'Date_Receiving' => Carbon::now(),
        ]);

        return back()->with('success', 'Barang telah diterima.');
    }

    /**
     * QC clicks "Selesai" - marks QC process as finished
     */
    public function finish($id, Request $request)
    {
        $request->validate([
            'Desc_Finish' => 'required|string|max:255',
        ]);

        $withdrawal = Withdrawal::findOrFail($id);
        $withdrawal->update([
            'Finish_Receiving' => true,
            'Date_Finish_Receiving' => Carbon::now(),
            'Desc_Finish' => $request->Desc_Finish,
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
        if ($request->Code_Rack_Return !== 'DAICHI') {
            $rack = Rack::where('Code_Rack', $request->Code_Rack_Return)->first();
            if (!$rack) {
                return back()->withErrors(['Code_Rack_Return' => 'Kode rak tidak ditemukan.']);
            }

            if ($rack->Code_Item_Rack !== $withdrawal->Code_Item_Withdrawal) {
                return back()->withErrors(['Code_Rack_Return' => 'Kode rak tidak sesuai dengan kode part pengajuan! Part: ' . $withdrawal->Code_Item_Withdrawal . ', Rak: ' . $rack->Code_Item_Rack]);
            }
        }

        $withdrawal->update([
            'NIK_Return' => $request->NIK_Return,
            'Code_Rack_Return' => $request->Code_Rack_Return,
            'Date_Return' => Carbon::now(),
        ]);

        return back()->with('success', 'Barang telah dikembalikan ke rak oleh ' . $member->Name_Member);
    }

    /**
     * Export withdrawal data to Excel
     */
    public function export(Request $request)
    {
        $query = Withdrawal::query();

        $status = $request->input('status', 'all');
        if ($status == 'unfinished') {
            $query->whereNull('Date_Return');
        } elseif ($status == 'finished') {
            $query->whereNotNull('Date_Return');
        }

        $date = $request->input('date');
        $month = $request->input('month');

        if ($date) {
            $query->whereDate('Date_Withdrawal', $date);
        } elseif ($month) {
            $parsedMonth = Carbon::parse($month . '-01');
            $query->whereMonth('Date_Withdrawal', $parsedMonth->format('m'))
                  ->whereYear('Date_Withdrawal', $parsedMonth->format('Y'));
        } else {
            $query->whereMonth('Date_Withdrawal', Carbon::now()->format('m'))
                  ->whereYear('Date_Withdrawal', Carbon::now()->format('Y'));
        }

        $withdrawals = $query->orderBy('Id_Withdrawal', 'desc')->get();
        $total = $withdrawals->count();

        $niks = $withdrawals->pluck('NIK_Withdrawal')
            ->merge($withdrawals->pluck('NIK_Return'))
            ->filter()
            ->unique();

        $membersMap = [];
        $usersMap = [];
        if ($niks->isNotEmpty()) {
            $members = Member::whereIn('NIK_Member', $niks)->get()->keyBy('NIK_Member');
            foreach ($members as $nik => $member) {
                $membersMap[$nik] = $member->Name_Member;
            }
            $users = \App\Models\User::whereIn('Id_User', $niks)->get()->keyBy('Id_User');
            foreach ($users as $id => $user) {
                $usersMap[$id] = $user->Username_User;
            }
        }

        $codes = $withdrawals->pluck('Code_Item_Withdrawal')->filter()->unique();
        $racksMap = [];
        if ($codes->isNotEmpty()) {
            $racks = Rack::whereIn('Code_Item_Rack', $codes)->get()->keyBy('Code_Item_Rack');
            foreach ($racks as $code => $rack) {
                $racksMap[$code] = [
                    'name' => $rack->Name_Item_Rack,
                    'no' => $rack->Code_Rack
                ];
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'No', 'Date WD', 'Name PIC', 'Item Code', 'Name Item', 'No Rack',
            'Oke DST', 'PIC DST', 'Date Oke',
            'Sampai di QC', 'Date Sampai QC',
            'Received', 'Date Received', 'Finish', 'Date Finish', 'Description Finish',
            'PIC Return', 'No Rack Return', 'Date Return'
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:S1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($withdrawals as $index => $w) {
            $noUrut = $total - $index;
            $nameDisiapkan = '-';
            if ($w->NIK_Withdrawal) {
                if ($w->Is_User && isset($usersMap[$w->NIK_Withdrawal])) {
                    $nameDisiapkan = $usersMap[$w->NIK_Withdrawal] . ' (Admin)';
                } elseif (isset($membersMap[$w->NIK_Withdrawal])) {
                    $nameDisiapkan = $membersMap[$w->NIK_Withdrawal];
                } elseif (isset($usersMap[$w->NIK_Withdrawal])) {
                    $nameDisiapkan = $usersMap[$w->NIK_Withdrawal] . ' (Admin)';
                } else {
                    $nameDisiapkan = $w->NIK_Withdrawal;
                }
            }

            $nameReturn = '-';
            if ($w->NIK_Return) {
                if ($w->Is_User && isset($usersMap[$w->NIK_Return])) {
                    $nameReturn = $usersMap[$w->NIK_Return] . ' (Admin)';
                } elseif (isset($membersMap[$w->NIK_Return])) {
                    $nameReturn = $membersMap[$w->NIK_Return];
                } elseif (isset($usersMap[$w->NIK_Return])) {
                    $nameReturn = $usersMap[$w->NIK_Return] . ' (Admin)';
                } else {
                    $nameReturn = $w->NIK_Return;
                }
            }
            
            $rackInfo = $racksMap[$w->Code_Item_Withdrawal] ?? null;

            $sheet->fromArray([
                $noUrut,
                $w->Date_Withdrawal ? Carbon::parse($w->Date_Withdrawal)->format('d/m/Y H:i') : '-',
                $w->Name_Withdrawal ?? '-',
                $w->Code_Item_Withdrawal ?? '-',
                $rackInfo ? $rackInfo['name'] : '-',
                $rackInfo ? $rackInfo['no'] : '-',
                $w->Oke_Withdrawal ? 'OK' : 'Pending',
                $w->Oke_Withdrawal ? $nameDisiapkan : '-',
                $w->Oke_Withdrawal && $w->Date_Withdrawal ? Carbon::parse($w->Date_Withdrawal)->format('d/m/Y H:i') : '-',
                $w->Arrive_Qc ? 'Ya' : 'Belum',
                $w->Date_Arrive_Qc ? Carbon::parse($w->Date_Arrive_Qc)->format('d/m/Y H:i') : '-',
                $w->Oke_Receiving ? 'Diterima' : '-',
                $w->Date_Receiving ? Carbon::parse($w->Date_Receiving)->format('d/m/Y H:i') : '-',
                $w->Finish_Receiving ? 'Selesai' : '-',
                $w->Date_Finish_Receiving ? Carbon::parse($w->Date_Finish_Receiving)->format('d/m/Y H:i') : '-',
                $w->Desc_Finish ?? '-',
                $w->Date_Return ? $nameReturn : '-',
                $w->Code_Rack_Return ?? '-',
                $w->Date_Return ? Carbon::parse($w->Date_Return)->format('d/m/Y H:i') : '-',
            ], null, 'A' . $row);

            $row++;
        }

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "QC_Withdrawal_" . Carbon::now()->format('Y-m-d') . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
