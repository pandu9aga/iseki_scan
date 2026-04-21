<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\Member;
use App\Models\Rack;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UserWithdrawalController extends Controller
{
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
            // For admin integration
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

        // Assign tanpa query tambahan
        foreach ($withdrawals as $w) {
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

            $rackInfo = $racksMap[$w->Code_Item_Withdrawal] ?? null;
            $w->rack_name = $rackInfo ? $rackInfo['name'] : '-';
            $w->rack_no = $rackInfo ? $rackInfo['no'] : '-';
        }

        return view('users.withdrawal.index', compact('withdrawals'));
    }

    public function oke($id, Request $request)
    {
        $nikMember = session('NIK_Member');
        if (!$nikMember) {
            return back()->withErrors(['error' => 'Session expired. Silakan login ulang.']);
        }

        try {
            $withdrawal = Withdrawal::findOrFail($id);

            // Guard: Cegah double-click
            if ($withdrawal->Oke_Withdrawal) {
                return back()->withErrors(['error' => 'Item sudah disiapkan sebelumnya.']);
            }

            $withdrawal->update([
                'Oke_Withdrawal' => true,
                'NIK_Withdrawal' => $nikMember,
            ]);

            $nameMember = session('Name_Member', 'Member');
            return back()->with('success', 'Withdrawal disiapkan oleh ' . $nameMember);
        } catch (\Exception $e) {
            Log::error('Withdrawal oke failed: ' . $e->getMessage(), [
                'withdrawal_id' => $id,
                'user_nik' => $nikMember
            ]);
            return back()->withErrors(['error' => 'Gagal memproses permintaan.']);
        }
    }

    public function returnRack($id, Request $request)
    {
        // ✅ AMBIL DARI SESSION, BUKAN INPUT!
        $nikMember = session('NIK_Member');
        if (!$nikMember) {
            return back()->withErrors(['error' => 'Session expired. Silakan login ulang.']);
        }

        // ✅ HANYA validasi barcode, NIK dari session
        $request->validate([
            'Code_Rack_Return' => 'required|string|max:100',
        ]);

        try {
            // ✅ Validasi member dari session
            $member = Member::where('NIK_Member', $nikMember)->first();
            if (!$member) {
                return back()->withErrors(['error' => 'Data member tidak valid.']);
            }

            $withdrawal = Withdrawal::findOrFail($id);

            // ✅ Guard: Cek workflow state
            if (!$withdrawal->Finish_Receiving) {
                return back()->withErrors(['error' => 'QC belum menyelesaikan proses.']);
            }
            if ($withdrawal->Date_Return) {
                return back()->withErrors(['error' => 'Barang sudah dikembalikan.']);
            }

            // Validasi rack & barcode
            $rack = Rack::where('Code_Rack', $request->Code_Rack_Return)->first();
            if (!$rack) {
                return back()->withErrors(['Code_Rack_Return' => 'Barcode rak tidak dikenali.']);
            }

            // ✅ Normalisasi perbandingan string
            if (trim(strtolower($rack->Code_Item_Rack)) !== trim(strtolower($withdrawal->Code_Item_Withdrawal))) {
                return back()->withErrors([
                    'Code_Rack_Return' => 'Salah Barang! Discan: ' . $rack->Code_Item_Rack .
                        ', Seharusnya: ' . $withdrawal->Code_Item_Withdrawal
                ]);
            }

            // ✅ Update pakai NIK dari session
            $withdrawal->update([
                'NIK_Return' => $nikMember,
                'Code_Rack_Return' => $request->Code_Rack_Return,
                'Date_Return' => Carbon::now(),
            ]);

            return back()->with('success', 'Barang dikembalikan ke rak oleh ' . $member->Name_Member);
        } catch (\Exception $e) {
            Log::error('Withdrawal return failed: ' . $e->getMessage(), [
                'withdrawal_id' => $id,
                'user_nik' => $nikMember
            ]);
            return back()->withErrors(['error' => 'Gagal mengembalikan barang.']);
        }
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
        }

        $withdrawals = $query->orderBy('Id_Withdrawal', 'desc')->get();

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
            'Received', 'Date Received', 'Finish', 'Date Finish', 'Description Finish',
            'PIC Return', 'No Rack Return', 'Date Return'
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($withdrawals as $index => $w) {
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
                $index + 1,
                $w->Date_Withdrawal ? Carbon::parse($w->Date_Withdrawal)->format('d/m/Y H:i') : '-',
                $w->Name_Withdrawal ?? '-',
                $w->Code_Item_Withdrawal ?? '-',
                $rackInfo ? $rackInfo['name'] : '-',
                $rackInfo ? $rackInfo['no'] : '-',
                $w->Oke_Withdrawal ? 'OK' : 'Pending',
                $w->Oke_Withdrawal ? $nameDisiapkan : '-',
                $w->Oke_Withdrawal && $w->Date_Withdrawal ? Carbon::parse($w->Date_Withdrawal)->format('d/m/Y H:i') : '-',
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

        $fileName = "QC_Withdrawal_Member_" . Carbon::now()->format('Y-m-d') . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
