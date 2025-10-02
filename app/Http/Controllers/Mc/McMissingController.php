<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;           // untuk HTTP Request
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Request as RequestModel; // alias model Request supaya gak bentrok
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class McMissingController extends Controller
{
    public function index(){
        $date = Carbon::today()->format('Y-m-d');
        $requests = RequestModel::with('member', 'record', 'rack')
            ->where('Status_Request', '!=', 'Done')
            ->whereRaw("TIMESTAMP(Day_Request, Time_Request) < ?", [Carbon::now()->subHours(48)])
            ->orderBy('Day_Request', 'desc')
            ->orderBy('Time_Request', 'desc')
            ->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequests = $requests->count();

        // $correct = $requests->filter(fn($request) => $request->Correctness_Request == 1)->count();
        // $incorrect = $totalRequests - $correct;

        return view('mcs.missings.index', compact('requests', 'totalRequests', 'formattedDate', 'date'));
    }

    public function export(Request $request) {
        $date = $request->input('Day_Request_Hidden');
        $date = Carbon::parse($date)->format('Y-m-d');
        $requests = RequestModel::with('member', 'record', 'rack')
            ->where('Status_Request', '!=', 'Done')
            ->whereRaw("TIMESTAMP(Day_Request, Time_Request) < ?", [Carbon::now()->subHours(48)])
            ->orderBy('Day_Request', 'desc')
            ->orderBy('Time_Request', 'desc')
            ->get();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = ['No', 'Rack', 'Item', 'Name', 'Sum', 'Time Request', 'Overdue', 'PIC'];
        $sheet->fromArray([$headers], NULL, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Isi data
        $row = 2;
        foreach ($requests as $index => $request) {
            $now = \Carbon\Carbon::now();
            $requestDateTime = \Carbon\Carbon::parse($request->Day_Request . ' ' . $request->Time_Request);
            $interval = $requestDateTime->diff($now);

            $timeRequest = ($request->Day_Request ?? '') . " " . ($request->Time_Request ?? '');

            $sheet->fromArray([
                $index + 1,
                $request->Code_Rack,
                $request->Code_Item_Rack,
                $request->rack->Name_Item_Rack,
                $request->Sum_Request,
                $timeRequest,
                $interval->d . ' day(s) ' . $interval->h . ' hour(s) ' . $interval->i . ' minute(s)',
                $request->member->Name_Member ?? '-',
            ], null, 'A' . $row);

            $row++;
        }

        // 🔑 Auto size kolom
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan ke file di storage/app/public
        $fileName = "Missing_List_" . $date . ".xlsx";
        $filePath = storage_path('app/public/' . $fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
