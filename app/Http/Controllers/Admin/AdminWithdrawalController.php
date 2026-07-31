<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\Member;
use App\Models\Rack;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AdminWithdrawalController extends Controller
{
    /**
     * Show withdrawal table with all records — Admin version (with actions)
     * Uses Is_User flag = 1 and session('Id_User') for admin identity
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
            $users = User::whereIn('Id_User', $niks)->get()->keyBy('Id_User');
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
        $total = $withdrawals->count();
        foreach ($withdrawals as $index => $w) {
            $w->no_urut = $total - $index;
            // we first check if Is_User is true, if so prioritize User name over Member name
            if ($w->NIK_Withdrawal) {
                if ($w->Is_User && isset($usersMap[$w->NIK_Withdrawal])) {
                    $w->name_disiapkan = $usersMap[$w->NIK_Withdrawal] . ' (Admin)';
                } elseif (isset($membersMap[$w->NIK_Withdrawal])) {
                    $w->name_disiapkan = $membersMap[$w->NIK_Withdrawal];
                } elseif (isset($usersMap[$w->NIK_Withdrawal])) {
                    $w->name_disiapkan = $usersMap[$w->NIK_Withdrawal] . ' (Admin)';
                } else {
                    $w->name_disiapkan = $w->NIK_Withdrawal;
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
                    $w->name_return = $w->NIK_Return;
                }
            } else {
                $w->name_return = '-';
            }

            $rackInfo = $racksMap[$w->Code_Item_Withdrawal] ?? null;
            $w->rack_name = $rackInfo ? $rackInfo['name'] : '-';
            $w->rack_no = $rackInfo ? $rackInfo['no'] : '-';
        }

        return view('admins.withdrawal.index', compact('withdrawals'));
    }

    /**
     * Admin OK Siapkan — uses session('Id_User') with 'ADMIN_' prefix as identifier
     */
    public function oke($id, Request $request)
    {
        $adminId = session('Id_User');
        if (!$adminId) {
            return back()->withErrors(['error' => 'Session expired. Silakan login ulang.']);
        }

        try {
            $withdrawal = Withdrawal::findOrFail($id);

            // Guard: Cegah double-click
            if ($withdrawal->Oke_Withdrawal) {
                return back()->withErrors(['error' => 'Item sudah disiapkan sebelumnya.']);
            }

            $isUser = $request->has('Is_User');
            $nikWd = $isUser ? $adminId : $request->input('NIK_Withdrawal');

            if (!$isUser && empty($nikWd)) {
                return back()->withErrors(['error' => 'NIK Member harus diisi jika tidak dilakukan sebagai Admin.']);
            }

            $withdrawal->update([
                'Oke_Withdrawal' => true,
                'NIK_Withdrawal' => $nikWd,
                'Is_User' => $isUser ? 1 : 0,
                'Date_Oke_Withdrawal' => Carbon::now(),
            ]);

            $username = $isUser ? session('Username_User', 'Admin') . ' (Admin)' : 'Member dengan NIK ' . $nikWd;
            return back()->with('success', 'Withdrawal disiapkan oleh ' . $username);
        } catch (\Exception $e) {
            Log::error('Admin Withdrawal oke failed: ' . $e->getMessage(), [
                'withdrawal_id' => $id,
                'admin_id' => $adminId
            ]);
            return back()->withErrors(['error' => 'Gagal memproses permintaan.']);
        }
    }

    /**
     * Admin confirm item arrived at QC
     */
    public function arrive($id)
    {
        $adminId = session('Id_User');
        if (!$adminId) {
            return back()->withErrors(['error' => 'Session expired. Silakan login ulang.']);
        }

        try {
            $withdrawal = Withdrawal::findOrFail($id);

            // Guard: Cegah double-click
            if ($withdrawal->Arrive_Qc) {
                return back()->withErrors(['error' => 'Item sudah ditaruh di QC sebelumnya.']);
            }
            if (!$withdrawal->Oke_Withdrawal) {
                return back()->withErrors(['error' => 'Item belum disiapkan.']);
            }

            $withdrawal->update([
                'Arrive_Qc' => true,
                'Date_Arrive_Qc' => Carbon::now(),
            ]);

            return back()->with('success', 'Barang berhasil ditaruh di QC.');
        } catch (\Exception $e) {
            Log::error('Admin Withdrawal arrive failed: ' . $e->getMessage(), [
                'withdrawal_id' => $id,
                'admin_id' => $adminId
            ]);
            return back()->withErrors(['error' => 'Gagal memproses permintaan.']);
        }
    }

    /**
     * Admin marks QC process as finished
     */
    public function finish($id, Request $request)
    {
        $adminId = session('Id_User');
        if (!$adminId) {
            return back()->withErrors(['error' => 'Session expired. Silakan login ulang.']);
        }

        $request->validate([
            'Desc_Finish' => 'required|string|max:255',
        ]);

        try {
            $withdrawal = Withdrawal::findOrFail($id);

            $withdrawal->update([
                'Finish_Receiving' => true,
                'Date_Finish_Receiving' => Carbon::now(),
                'Desc_Finish' => $request->Desc_Finish,
            ]);

            return back()->with('success', 'QC telah selesai.');
        } catch (\Exception $e) {
            Log::error('Admin Withdrawal finish failed: ' . $e->getMessage(), [
                'withdrawal_id' => $id,
                'admin_id' => $adminId
            ]);
            return back()->withErrors(['error' => 'Gagal memproses permintaan.']);
        }
    }

    /**
     * Admin Return to Rack — uses session('Id_User') with 'ADMIN_' prefix
     */
    public function returnRack($id, Request $request)
    {
        $adminId = session('Id_User');
        if (!$adminId) {
            return back()->withErrors(['error' => 'Session expired. Silakan login ulang.']);
        }

        $request->validate([
            'Code_Rack_Return' => 'required|string|max:100',
        ]);

        try {
            $withdrawal = Withdrawal::findOrFail($id);

            // Guard: Cek workflow state
            if (!$withdrawal->Finish_Receiving) {
                return back()->withErrors(['error' => 'QC belum menyelesaikan proses.']);
            }
            if ($withdrawal->Date_Return) {
                return back()->withErrors(['error' => 'Barang sudah dikembalikan.']);
            }

            // Validasi rack & barcode
            if ($request->Code_Rack_Return !== 'DAICHI') {
                $rack = Rack::where('Code_Rack', $request->Code_Rack_Return)->first();
                if (!$rack) {
                    return back()->withErrors(['Code_Rack_Return' => 'Barcode rak tidak dikenali.']);
                }

                // Normalisasi perbandingan string
                if (trim(strtolower($rack->Code_Item_Rack)) !== trim(strtolower($withdrawal->Code_Item_Withdrawal))) {
                    return back()->withErrors([
                        'Code_Rack_Return' => 'Salah Barang! Discan: ' . $rack->Code_Item_Rack .
                            ', Seharusnya: ' . $withdrawal->Code_Item_Withdrawal
                    ]);
                }
            }

            $isUser = $request->has('Is_User');
            $nikRet = $isUser ? $adminId : $request->input('NIK_Return');

            if (!$isUser && empty($nikRet)) {
                return back()->withErrors(['error' => 'NIK Member harus diisi jika tidak dilakukan sebagai Admin.']);
            }

            $withdrawal->update([
                'NIK_Return' => $nikRet,
                'Code_Rack_Return' => $request->Code_Rack_Return,
                'Date_Return' => Carbon::now(),
                'Is_User' => $isUser ? 1 : 0,
            ]);

            $username = $isUser ? session('Username_User', 'Admin') . ' (Admin)' : 'Member dengan NIK ' . $nikRet;
            return back()->with('success', 'Barang dikembalikan ke rak oleh ' . $username);
        } catch (\Exception $e) {
            Log::error('Admin Withdrawal return failed: ' . $e->getMessage(), [
                'withdrawal_id' => $id,
                'admin_id' => $adminId
            ]);
            return back()->withErrors(['error' => 'Gagal mengembalikan barang.']);
        }
    }

    /**
     * Admin deletes withdrawal — can delete regardless of status
     */
    public function destroy($id)
    {
        try {
            $withdrawal = Withdrawal::findOrFail($id);
            $withdrawal->delete();
            return back()->with('success', 'Data withdrawal berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Admin Withdrawal delete failed: ' . $e->getMessage(), ['withdrawal_id' => $id]);
            return back()->withErrors(['error' => 'Gagal menghapus data: ' . $e->getMessage()]);
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
        } else {
            $query->whereMonth('Date_Withdrawal', Carbon::now()->format('m'))
                  ->whereYear('Date_Withdrawal', Carbon::now()->format('Y'));
        }

        $withdrawals = $query->orderBy('Id_Withdrawal', 'desc')->get();
        $total = $withdrawals->count();

        // Build maps
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
            $users = User::whereIn('Id_User', $niks)->get()->keyBy('Id_User');
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
            // Resolve names - check Is_User flag first to prioritize User over Member if IDs collide
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
                $w->Oke_Withdrawal && $w->Date_Oke_Withdrawal ? Carbon::parse($w->Date_Oke_Withdrawal)->format('d/m/Y H:i') : '-',
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
