<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Record;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ReportController extends Controller
{
    public function index(){
        $date = Carbon::today();
        $records = Record::whereDate('Day_Record', $date)->orderBy('Time_Record', 'desc')->with('member', 'request')->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRecords = $records->count();
        $date = Carbon::parse($date)->isoFormat('YYYY-MM-DD');
        
        $correct = $records->filter(function ($record) {
            return $record->Correctness_Record == 1;
        })->count();
        $incorrect = $records->count() - $correct;

        return view('admins.reports.index', compact('records','totalRecords', 'correct', 'incorrect','formattedDate','date'));
    }

    public function submit(Request $request){
        $date = $request->input('Day_Record');
        $records = Record::whereDate('Day_Record', $date)->orderBy('Time_Record', 'desc')->with('member', 'request')->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRecords = $records->count();
        
        $correct = $records->filter(function ($record) {
            return $record->Correctness_Record == 1;
        })->count();
        $incorrect = $records->count() - $correct;

        return view('admins.reports.index', compact('records','totalRecords', 'correct', 'incorrect','formattedDate','date'));
    }

    public function export(Request $request) {
        $date = $request->input('Day_Record_Hidden');
        $date = Carbon::parse($date)->format('Y-m-d');
        $records = Record::whereDate('Day_Record', $date)->with('member', 'request')->get();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = [
            'No',
            'Time Request',
            'Time Record',
            'Item',
            'Rack',
            'Sum Request',
            'Sum Record',
            'Member',
            'Correctness',
            'Updated'
        ];
        $sheet->fromArray([$headers], NULL, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        // Isi data
        $row = 2;
        foreach ($records as $index => $record) {
            $correctness = $record->Correctness_Record == 1 ? 'Correct' : 'Incorrect';

            $sheet->fromArray([
                $index + 1,
                optional($record->request)->Time_Request ?? '',
                $record->Time_Record,
                $record->Code_Item_Rack,
                $record->Code_Rack,
                optional($record->request)->Sum_Request ?? '',
                $record->Sum_Record,
                $record->member->Name_Member ?? '-',
                $correctness,
                $record->Updated_At_Record ?? '',
            ], NULL, 'A' . $row);

            // Set warna dan tebal untuk "Correct" & "Incorrect"
            $correctnessCell = 'I' . $row;
            if ($correctness === 'Correct') {
                $sheet->getStyle($correctnessCell)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '008000']] // Hijau
                ]);
            } else {
                $sheet->getStyle($correctnessCell)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']] // Merah
                ]);
            }

            $row++;
        }

        // Auto-size kolom sesuai konten
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan ke file
        $fileName = "Record_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
