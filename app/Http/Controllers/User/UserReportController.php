<?php

namespace App\Http\Controllers\User;

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

class UserReportController extends Controller
{
    public function index(){
        $date = Carbon::today();
        $records = Record::whereDate('Day_Record', $date)->where('Id_User', session('Id_Member'))->orderBy('Time_Record', 'desc')->with('member', 'request')->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRecords = $records->count();
        $date = Carbon::parse($date)->isoFormat('YYYY-MM-DD');

        $correct = $records->filter(function ($record) {
            return $record->Correctness_Record == 1;
        })->count();
        $incorrect = $records->count() - $correct;

        return view('users.reports.index', compact('records','totalRecords', 'correct', 'incorrect','formattedDate','date'));
    }

    public function submit(Request $request){
        $date = $request->input('Day_Record');
        $records = Record::whereDate('Day_Record', $date)->where('Id_User', session('Id_Member'))->orderBy('Time_Record', 'desc')->with('member', 'request')->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRecords = $records->count();

        $correct = $records->filter(function ($record) {
            return $record->Correctness_Record == 1;
        })->count();
        $incorrect = $records->count() - $correct;

        return view('users.reports.index', compact('records','totalRecords', 'correct', 'incorrect','formattedDate','date'));
    }

    public function export(Request $request) {
        $date = $request->input('Day_Record_Hidden');
        $date = Carbon::parse($date)->format('Y-m-d');
        $records = Record::whereDate('Day_Record', $date)->where('Id_User', session('Id_Member'))->with('member', 'request')->get();
        $name = Member::where('Id_Member', session('Id_Member'))->value('Name_Member');
    
        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = ['No', 'Date', 'Time', 'Item', 'Rack', 'Sum Record', 'Correctness', 'Person'];
        $sheet->fromArray([$headers], NULL, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
    
        // Isi data
        $row = 2;
        foreach ($records as $index => $record) {
            $correctness = $record->Correctness_Record == 1 ? 'Correct' : 'Incorrect';

            // Tambahkan data ke Excel
            $sheet->fromArray([
                $index + 1,
                $date,
                $record->Time_Record,
                $record->Code_Item_Rack,
                $record->Code_Rack,
                $record->Sum_Record,
                $correctness,
                $name
            ], NULL, 'A' . $row);

            // Set warna dan tebal untuk "Correct" & "Incorrect"
            $correctnessCell = 'G' . $row;
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
    
        // Simpan ke file
        $fileName = "Record_" . $name . "_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);
    
        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
