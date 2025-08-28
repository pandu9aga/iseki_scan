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

class MonthlyController extends Controller
{
    public function index(){
        $date = Carbon::today();
        $records = Record::orderBy('Day_Record', 'desc')->orderBy('Time_Record', 'desc')->with('member', 'request', 'rack')->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRecords = $records->count();
        $date = Carbon::parse($date)->isoFormat('YYYY-MM-DD');
        
        $correct = $records->filter(function ($record) {
            return $record->Correctness_Record == 1;
        })->count();
        $incorrect = $records->count() - $correct;

        return view('admins.monthlys.index', compact('records','totalRecords', 'correct', 'incorrect','formattedDate','date'));
    }

    public function export(Request $request) {
        $date = $request->input('Day_Record_Hidden');
        $date = Carbon::parse($date)->format('Y-m-d');

        // Ambil data
        $records = Record::with('member', 'request', 'rack')
            ->leftJoin('requests', 'records.Id_Request', '=', 'requests.Id_Request')
            ->select('records.*') // supaya tetap model Record
            ->orderBy('records.Id_User', 'asc')
            ->orderByRaw("COALESCE(requests.Area_Request, '') asc") // null duluan
            ->orderBy('records.Day_Record', 'asc')
            ->orderBy('records.Time_Record', 'asc')
            ->get();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = [
            'No', 'Time Record', 'Area', 'Rack', 'Sum Record',
            'Item', 'Name', 'Correctness', 'Time Request',
            'Sum Request', 'Member', 'Updated'
        ];
        $sheet->fromArray([$headers], NULL, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        // Isi data
        $row = 2;
        $lastUser = null;
        $no = 1;

        foreach ($records as $record) {
            // Reset nomor & kasih spasi kalau ganti user
            if ($lastUser !== null && $lastUser != $request->Id_User) {
                $sheet->fromArray(
                    array_fill(0, 12, '-'), // 12 kolom sesuai header
                    null,
                    'A' . $row
                );
                $row++;
                $no = 1; // reset nomor
            }

            $correctness = $record->Correctness_Record == 1 ? 'Correct' : 'Incorrect';
            $timeRecord = ($record->Day_Record ?? '') . " " . ($record->Time_Record ?? '');
            $timeRequest = ($record->request->Day_Request ?? '') . " " . ($record->request->Time_Request ?? '');

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
                $record->member->Name_Member ?? '-',
                $record->Updated_At_Record ?? '',
            ], NULL, 'A' . $row);

            // Warna correctness
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

        // Auto-size kolom
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan
        $fileName = "Record_Keseluruhan_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function reset(){
        Record::truncate();
        return redirect()->route('monthly')->with('success', 'Records successfully reset.');
    }
}
