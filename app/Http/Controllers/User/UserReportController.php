<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Record;
use App\Models\Request as RequestModel;
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
        $records = Record::whereDate('Day_Record', $date)->where('Id_User', session('Id_Member'))->orderBy('Time_Record', 'desc')->with('member', 'request', 'rack')->get();
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
        $records = Record::whereDate('Day_Record', $date)->where('Id_User', session('Id_Member'))->orderBy('Time_Record', 'desc')->with('member', 'request', 'rack')->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRecords = $records->count();

        $correct = $records->filter(function ($record) {
            return $record->Correctness_Record == 1;
        })->count();
        $incorrect = $records->count() - $correct;

        return view('users.reports.index', compact('records','totalRecords', 'correct', 'incorrect','formattedDate','date'));
    }

    public function export(Request $request)
    {
        $date = $request->input('Day_Record_Hidden');
        $date = Carbon::parse($date)->format('Y-m-d');

        // $records = Record::whereDate('Day_Record', $date)
        //     ->where('Id_User', session('Id_Member'))
        //     ->with('member', 'request', 'rack')
        //     ->get()
        //     ->sortBy(function($record) {
        //         return $record->request->Area_Request ?? '';
        //     });

        $records = Record::with('member', 'request', 'rack')
            ->leftJoin('requests', 'records.Id_Request', '=', 'requests.Id_Request')
            ->select('records.*') // supaya tetap model Record
            ->whereDate('records.Day_Record', $date)
            ->where('records.Id_User', session('Id_Member'))
            ->orderByRaw("COALESCE(requests.Area_Request, '') asc") // null duluan
            ->orderBy('records.Day_Record', 'asc')
            ->orderBy('records.Time_Record', 'asc')
            ->get();

        $name = Member::where('Id_Member', session('Id_Member'))->value('Name_Member');

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = [
            'No',
            'Time Record',
            'Area',
            'Rack',
            'Sum Record',
            'Item',
            'Name',
            'Correctness',
            'Time Request',
            'Sum Request',
            'Member',
            'Updated'
        ];
        $sheet->fromArray([$headers], NULL, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

        // Isi data
        $row = 2;
        foreach ($records as $index => $record) {
            $correctness = $record->Correctness_Record == 1 ? 'Correct' : 'Incorrect';
            $timeRecord = ($record->Day_Record ?? '') . " " . ($record->Time_Record ?? '');
            $timeRequest = ($record->request->Day_Request ?? '') . " " . ($record->request->Time_Request ?? '');

            $sheet->fromArray([
                $index + 1,
                $timeRecord,
                optional($record->request)->Area_Request ?? '',
                $record->Code_Rack,
                $record->Sum_Record,
                $record->Code_Item_Rack,
                $record->rack->Name_Item_Rack,
                $correctness,
                $timeRequest,
                optional($record->request)->Sum_Request ?? '',
                $record->member->Name_Member ?? $name,
                $record->Updated_At_Record ?? '',
            ], NULL, 'A' . $row);

            // Warna khusus untuk Correctness
            $correctnessCell = 'H' . $row;
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
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan ke file
        $fileName = "Record_" . $name . "_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function update(Request $request, $id)
    {
        $req = Record::findOrFail($id);

        $request->validate([
            'Sum_Record' => 'required|integer|min:1',
        ]);

        $req->Sum_Record = $request->Sum_Record;
        $req->Updated_At_Record = now(); // isi timestamp
        $req->save();

        return redirect()->back()->with('success', 'Record berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $record = Record::findOrFail($id);

        if ($record->Id_Request) {
            // update status request jadi Waiting
            $request = RequestModel::find($record->Id_Request);
            if ($request) {
                $request->Status_Request = 'Waiting';
                $request->save();
            }
        }

        // hapus record
        $record->delete();

        return redirect()->back()->with('success', 'Record berhasil dihapus.');
    }
}
