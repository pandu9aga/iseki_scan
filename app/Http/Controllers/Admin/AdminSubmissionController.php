<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;           // untuk HTTP Request
use Carbon\Carbon;
use App\Models\Request as RequestModel; // alias model Request supaya gak bentrok
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AdminSubmissionController extends Controller
{
    public function index(){
        $date = Carbon::today()->format('Y-m-d');
        $requests = RequestModel::with('member', 'record', 'rack')->orderBy('Day_Request', 'desc')->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequests = $requests->count();

        $correct = $requests->filter(fn($request) => $request->Correctness_Request == 1)->count();
        $incorrect = $totalRequests - $correct;

        return view('admins.submissions.index', compact('requests', 'totalRequests', 'correct', 'incorrect', 'formattedDate', 'date'));
    }

    public function export(Request $request) {
        $date = $request->input('Day_Request_Hidden');
        $date = Carbon::parse($date)->format('Y-m-d');
        $requests = RequestModel::with('member', 'record', 'rack')->orderBy('Day_Request', 'desc')->get();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = ['No', 'Time Request', 'Time Record', 'Item', 'Rack', 'Name', 'Sum Request', 'Sum Record', 'Member', 'Updated'];
        $sheet->fromArray([$headers], NULL, 'A1');

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

        // Simpan ke file di storage/app/public
        $fileName = "Request_Keseluruhan_" . $date . ".xlsx";
        $filePath = storage_path('app/public/' . $fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function reset(Request $request)
    {
        // Hapus semua data di tabel requests
        RequestModel::truncate();

        // Redirect ke halaman index setelah data dihapus
        return redirect()->route('admin_submission')->with('success', 'All requests have been deleted.');
    }
}
