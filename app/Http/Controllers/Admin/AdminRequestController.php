<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Request as RequestModel;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AdminRequestController extends Controller
{
    public function index()
    {
        $date = Carbon::today();
        $dateForInput = $date->format('Y-m-d');  // Untuk input date di view
        $requests = RequestModel::whereDate('Day_Request', $date)->with('member', 'record', 'rack')->orderBy('Time_Request', 'desc')->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequest = $requests->count();
        $date = Carbon::parse($date)->isoFormat('YYYY-MM-DD');

        $correct = $requests->filter(function ($request) {
            return $request->Correctness_Request == 1;
        })->count();
        $incorrect = $totalRequest - $correct;

        return view('admins.requests.index', compact('requests', 'totalRequest', 'correct', 'incorrect', 'formattedDate', 'date', 'dateForInput'));
    }

    public function submit(Request $request)
    {
        $date = $request->input('Day_Request');
        $dateForInput = Carbon::parse($date)->format('Y-m-d');
        $requests = RequestModel::whereDate('Day_Request', $date)->with('member', 'record', 'rack')->orderBy('Time_Request', 'desc')->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        
        $totalRequest = $requests->count();

        $correct = $requests->filter(function ($request) {
            return $request->Correctness_Request == 1;
        })->count();
        $incorrect = $totalRequest - $correct;

        return view('admins.requests.index', compact('requests', 'totalRequest', 'correct', 'incorrect', 'formattedDate', 'date', 'dateForInput'));
    }

    public function export(Request $request)
    {
        $date = $request->input('Day_Request_Hidden');
        $date = Carbon::parse($date)->format('Y-m-d');
        $requests = RequestModel::whereDate('Day_Request', $date)->with('member', 'record', 'rack')->get();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = ['No', 'Time Request', 'Time Record', 'Item', 'Rack', 'Name', 'Sum Request', 'Sum Record', 'Member', 'Updated'];
        $sheet->fromArray([$headers], null, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        // Isi data
        $row = 2;
        foreach ($requests as $index => $request) {
            $timeRequest = ($request->Day_Request ?? '') . " " . ($request->Time_Request ?? '');
            $timeRecord = ($request->record->Day_Record ?? '') . " " . ($request->record->Time_Record ?? '');

            $sheet->fromArray([
                $index + 1,
                $timeRequest,
                $timeRecord,
                $request->Code_Item_Rack,
                $request->Code_Rack,
                $request->rack->Name_Item_Rack ?? '',
                $request->Sum_Request,
                optional($request->record)->Sum_Record ?? '',
                $request->member->Name_Member ?? '-',
                $request->Updated_At_Request,
            ], null, 'A' . $row);

            $row++;
        }

        // 🔑 Auto size kolom
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan ke file
        $fileName = "Request_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
