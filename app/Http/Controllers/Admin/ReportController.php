<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Record;
use App\Models\Member;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ReportController extends Controller
{
    public function index(){
        $date = Carbon::today();
        $dateForInput = $date->format('Y-m-d');
        $memberId = request('Id_User'); // ambil filter member kalau ada

        $query = Record::whereDate('Day_Record', $date)
            ->orderBy('Time_Record', 'desc')
            ->with('member', 'request');
        
        if ($memberId) {
            $query->where('Id_User', $memberId);
        }

        $records = $query->get();
        
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRecords = $records->count();
        $date = Carbon::parse($date)->isoFormat('YYYY-MM-DD');
        
        $correct = $records->filter(function ($record) {
            return $record->Correctness_Record == 1;
        })->count();
        $incorrect = $records->count() - $correct;

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->orderBy('Name_Member')->get();

        return view('admins.reports.index', compact(
            'records','totalRecords', 'correct', 'incorrect','formattedDate','date', 'dateForInput', 'members'
        ));
    }

    public function submit(Request $request){
        $date = $request->input('Day_Record');
        $dateForInput = Carbon::parse($date)->format('Y-m-d');
        $memberId = $request->input('Id_User');

        $query = Record::whereDate('Day_Record', $date)
            ->orderBy('Time_Record', 'desc')
            ->with('member', 'request');

        if ($memberId) {
            $query->where('Id_User', $memberId);
        }

        $records = $query->get();
        
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRecords = $records->count();
        
        $correct = $records->filter(function ($record) {
            return $record->Correctness_Record == 1;
        })->count();
        $incorrect = $records->count() - $correct;

        $members = Member::orderBy('Name_Member')->get();

        return view('admins.reports.index', compact(
            'records','totalRecords', 'correct', 'incorrect','formattedDate','date', 'dateForInput', 'members'
        ));
    }

    public function export(Request $request) {
        $date = Carbon::parse($request->input('Day_Record_Hidden'))->format('Y-m-d');
        $memberId = $request->input('Id_User');

        // Ambil data dengan join requests supaya bisa order by Area_Request
        $query = Record::whereDate('records.Day_Record', $date)
            ->with('member', 'request', 'rack')
            ->leftJoin('requests', 'records.Id_Request', '=', 'requests.Id_Request')
            ->select('records.*') // supaya tetap model Record
            ->orderBy('records.Id_User', 'asc')
            ->orderByRaw("COALESCE(requests.Area_Request, '') asc") // null duluan
            ->orderBy('records.Day_Record', 'asc')
            ->orderBy('records.Time_Record', 'asc');

        // prefix table name supaya tidak ambiguous
        if ($memberId) {
            $query->where('records.Id_User', $memberId);
        }

        $records = $query->get();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = [
            'No', 'Time Record', 'Area', 'Rack', 'Sum Record',
            'Item', 'Name', 'Correctness', 'Time Request',
            'Sum Request', 'Sum Stock', 'Estimation Date', 'Member Request', 'Member Record', 'Updated'
        ];
        $sheet->fromArray([$headers], NULL, 'A1');

        // Style header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        // Isi data
        $row = 2;
        $lastUser = null;
        $no = 1;

        foreach ($records as $record) {
            // jika user berubah -> tambah satu baris pemisah yang berisi '-' lalu reset nomor
            if ($lastUser !== null && $record->Id_User != $lastUser) {
                $sheet->fromArray(array_fill(0, count($headers), '-'), null, 'A' . $row);
                $row++;
                $no = 1;
            }

            $correctness = $record->Correctness_Record == 1 ? 'Correct' : 'Incorrect';
            $timeRecord = ($record->Day_Record ?? '') . " " . ($record->Time_Record ?? '');
            $timeRequest = (optional($record->request)->Day_Request ?? '') . " " . (optional($record->request)->Time_Request ?? '');

            $statusCode = '';
            if (optional($record->request)->Ready_Request !== null) {
                $statusCode = '1';
            } elseif (optional($record->request)->Shipping_Request !== null) {
                $statusCode = '2';
            } elseif (optional($record->request)->Production_Area_Request !== null) {
                $statusCode = '3';
            } elseif (optional($record->request)->Design_Changes_Request !== null) {
                $statusCode = '4';
            }

            $sumStockDisplay = '';
            if ($statusCode == '2' || $statusCode == '4') {
                $sumStockDisplay = optional($record->request)->Stock_Shipping ?? '';
            } else {
                $sumStockDisplay = optional($record->request)->Sum_Stock ?? '';
            }

            $estimationDisplay = '';
            if (optional($record->request)->Estimation_Stock) {
                $estimationDisplay = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                    Carbon::parse(optional($record->request)->Estimation_Stock)
                );
            }

            $sheet->fromArray([
                $no,
                $timeRecord,
                optional($record->request)->Area_Request ?? '',
                $record->Code_Rack,
                $record->Sum_Record,
                $record->Code_Item_Rack,
                $record->rack->Name_Item_Rack ?? '',
                $correctness,
                $timeRequest,
                optional($record->request)->Sum_Request ?? '',
                $sumStockDisplay,
                $estimationDisplay,
                optional($record->request)->Is_User == 1 ? (optional($record->request->user)->Name_User ?? 'Admin') : (optional($record->request)->member->Name_Member ?? ''),
                $record->Is_User == 1 ? (optional($record->user)->Name_User ?? 'Admin') : ($record->member->Name_Member ?? ''),
                $record->Updated_At_Record ?? '',
            ], NULL, 'A' . $row);

            // warna Correct/Incorrect
            $correctnessCell = 'H' . $row;
            $sheet->getStyle($correctnessCell)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $correctness === 'Correct' ? '008000' : 'FF0000']
                ]
            ]);

            $lastUser = $record->Id_User;
            $no++;
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle('L2:L' . $lastRow)->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        }

        // Auto-size kolom
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan & download
        $fileName = "Record_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
