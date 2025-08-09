<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Request as RequestModel;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class McRequestController extends Controller
{
    public function index()
    {
        $date = Carbon::today();
        $dateForInput = $date->format('Y-m-d');  // Untuk input date di view
        $requests = RequestModel::whereDate('Day_Request', $date)->with('member')->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequest = $requests->count();
        $date = Carbon::parse($date)->isoFormat('YYYY-MM-DD');

        $correct = $requests->filter(function ($request) {
            return $request->Correctness_Request == 1;
        })->count();
        $incorrect = $totalRequest - $correct;

        return view('mcs.requests.index', compact('requests', 'totalRequest', 'correct', 'incorrect', 'formattedDate', 'date', 'dateForInput'));
    }

    public function submit(Request $request)
    {
        $date = $request->input('Day_Request');
        $dateForInput = Carbon::parse($date)->format('Y-m-d');
        $requests = RequestModel::whereDate('Day_Request', $date)->with('member')->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        
        $totalRequest = $requests->count();

        $correct = $requests->filter(function ($request) {
            return $request->Correctness_Request == 1;
        })->count();
        $incorrect = $totalRequest - $correct;

        return view('mcs.requests.index', compact('requests', 'totalRequest', 'correct', 'incorrect', 'formattedDate', 'date', 'dateForInput'));
    }

    public function export(Request $request)
    {
        $date = $request->input('Day_Request_Hidden');
        $date = Carbon::parse($date)->format('Y-m-d');
        $requests = RequestModel::whereDate('Day_Request', $date)->with('member')->get();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = ['No', 'Date', 'Time', 'Item', 'Rack', 'Person','Sum Request'];
        $sheet->fromArray([$headers], NULL, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        // Isi data
        $row = 2;
        foreach ($requests as $index => $request) {

            // Tambahkan data ke Excel, pastikan memasukkan string bukan objek
            $sheet->fromArray([
                $index + 1,
                $date,
                $request->Time_Request,
                $request->Code_Item_Rack,
                $request->Code_Rack,
                $request->member->Name_Member ?? '-',
                $request->Sum_Request
            ], NULL, 'A' . $row);

            $row++;
        }

        // Simpan ke file
        $fileName = "Request_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
