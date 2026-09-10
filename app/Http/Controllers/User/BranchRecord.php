<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BranchRecord as BranchRecordModel;
use App\Models\Member;
use App\Models\Rack;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BranchRecord extends Controller
{
    /**
     * Halaman form input Branch Record.
     */
    public function index()
    {
        return view('users.branch_records.index');
    }

    /**
     * Simpan data Branch Record baru (hanya jika nomor rak valid di master rak).
     */
    public function create(Request $request)
    {
        $date = Carbon::today()->format('Y-m-d');
        $timeNow = Carbon::now()->format('H:i:s');
        $Id_User = session('Id_Member');

        $validated = $request->validate([
            'Code_Rack' => 'required',
        ], [
            'Code_Rack.required' => 'Nomor/kode rak wajib diisi.',
        ]);

        $rawCodeRack = trim($validated['Code_Rack']);
        $upperCodeRack = strtoupper($rawCodeRack);

        // Validasi: hanya bisa disimpan jika terdaftar di master data rak
        $rack = Rack::where('Code_Rack', $upperCodeRack)
            ->orWhere('Code_Rack', $rawCodeRack)
            ->first();

        if (!$rack) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Kode '{$rawCodeRack}' bukan nomor rak yang valid atau tidak terdaftar dalam master rak.");
        }

        try {
            BranchRecordModel::create([
                'Day_Branch_Record'        => $date,
                'Time_Branch_Record'       => $timeNow,
                'Code_Rack'                => $rack->Code_Rack,
                'Id_User'                  => $Id_User,
                'Updated_At_Branch_Record' => null,
            ]);

            return redirect()->back()->with('success', 'Rak ' . $rack->Code_Rack . ' berhasil dicatat.');
        } catch (\Throwable $e) {
            Log::error('BranchRecord create failed: ' . $e->getMessage(), [
                'user_id' => $Id_User,
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Gagal menyimpan data rak. Silakan coba lagi.');
        }
    }

    /**
     * AJAX check apakah kode yang diinput adalah nomor rak yang valid.
     */
    public function checkRack(Request $request)
    {
        $code = trim($request->input('code', ''));
        if (!$code) {
            return response()->json(['valid' => false, 'message' => 'Kode kosong']);
        }

        $rack = Rack::where('Code_Rack', strtoupper($code))
            ->orWhere('Code_Rack', $code)
            ->first();

        return response()->json([
            'valid'     => (bool) $rack,
            'code_rack' => $rack ? $rack->Code_Rack : null,
            'message'   => $rack ? 'Nomor rak valid' : 'Bukan nomor rak terdaftar',
        ]);
    }

    /**
     * API JSON data Branch Record per tanggal (AJAX).
     */
    public function getData(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $myId = session('Id_Member');

        $records = BranchRecordModel::whereDate('Day_Branch_Record', $date)
            ->where('Id_User', $myId)
            ->orderBy('Time_Branch_Record', 'desc')
            ->get()
            ->map(function ($r) use ($myId) {
                return [
                    'id'        => $r->Id_Branch_Record,
                    'code_rack' => $r->Code_Rack,
                    'time'      => $r->Time_Branch_Record,
                    'user'      => $r->display_name,
                    'is_mine'   => ($r->Id_User == $myId),
                ];
            });

        return response()->json([
            'date'    => $date,
            'records' => $records,
            'count'   => $records->count(),
        ]);
    }

    /**
     * Halaman Branch Recording (tabel capaian & filter tanggal).
     */
    public function report(Request $request)
    {
        $inputDate = $request->input('Day_Branch_Record');
        $dateCarbon = $inputDate ? Carbon::parse($inputDate) : Carbon::today();
        $date = $dateCarbon->format('Y-m-d');
        $formattedDate = $dateCarbon->locale('en')->isoFormat('dddd, D-MMM-YY');

        $records = BranchRecordModel::whereDate('Day_Branch_Record', $date)
            ->where('Id_User', session('Id_Member'))
            ->orderBy('Time_Branch_Record', 'desc')
            ->with('member', 'rack')
            ->get();

        $totalRecords = $records->count();

        return view('users.branch_records.report', compact('records', 'totalRecords', 'formattedDate', 'date'));
    }

    /**
     * Export Excel data Branch Recording.
     */
    public function reportExport(Request $request)
    {
        $inputDate = $request->input('Day_Branch_Record_Hidden', $request->input('Day_Branch_Record'));
        $dateCarbon = $inputDate ? Carbon::parse($inputDate) : Carbon::today();
        $date = $dateCarbon->format('Y-m-d');

        $records = BranchRecordModel::with('member', 'rack')
            ->whereDate('Day_Branch_Record', $date)
            ->where('Id_User', session('Id_Member'))
            ->orderBy('Time_Branch_Record', 'asc')
            ->get();

        $name = Member::where('Id_Member', session('Id_Member'))->value('Name_Member') ?? 'User';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'No',
            'Time',
            'Rack',
            'Member',
            'Updated'
        ];
        $sheet->fromArray([$headers], NULL, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($records as $index => $record) {
            $timeRecord = ($record->Day_Branch_Record ?? '') . " " . ($record->Time_Branch_Record ?? '');

            $sheet->fromArray([
                $index + 1,
                $timeRecord,
                $record->Code_Rack,
                $record->display_name ?? '',
                $record->Updated_At_Branch_Record ?? '',
            ], NULL, 'A' . $row);

            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "Branch_Record_" . str_replace(' ', '_', $name) . "_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
