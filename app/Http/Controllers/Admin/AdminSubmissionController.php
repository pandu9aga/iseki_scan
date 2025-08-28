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
        $requests = RequestModel::with('member', 'record', 'rack')->orderBy('Day_Request', 'desc')->orderBy('Time_Request', 'desc')->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequests = $requests->count();

        $correct = $requests->filter(fn($request) => $request->Correctness_Request == 1)->count();
        $incorrect = $totalRequests - $correct;

        return view('admins.submissions.index', compact('requests', 'totalRequests', 'correct', 'incorrect', 'formattedDate', 'date'));
    }

    public function export(Request $request) {
        $date = $request->input('Day_Request_Hidden');
        $date = Carbon::parse($date)->format('Y-m-d');
        $requests = RequestModel::with('member', 'record', 'rack')
            ->orderBy('Id_User')
            ->orderBy('Urgent_Request', 'desc')
            ->orderBy('Area_Request')
            ->orderBy('Day_Request')
            ->orderBy('Time_Request')
            ->get();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = ['No', 'Time Request', 'Area', 'Rack', 'Sum Request', 'Urgenity', 'Item', 'Name', 'Time Record', 'Sum Record', 'Member', 'Updated'];
        $sheet->fromArray([$headers], NULL, 'A1');

        // Style header
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

        foreach ($requests as $index => $request) {
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

            $timeRequest = ($request->Day_Request ?? '') . " " . ($request->Time_Request ?? '');
            $timeRecord = ($request->record->Day_Record ?? '') . " " . ($request->record->Time_Record ?? '');

            $sheet->fromArray([
                $no,
                $timeRequest,
                $request->Area_Request ?? '',
                $request->Code_Rack,
                $request->Sum_Request,
                $request->Urgent_Request == 1 ? '✓' : '',
                $request->Code_Item_Rack,
                $request->rack->Name_Item_Rack ?? '',
                $timeRecord,
                optional($request->record)->Sum_Record ?? '',
                $request->member->Name_Member ?? '-',
                $request->Updated_At_Request,
            ], null, 'A' . $row);

            $lastUser = $request->Id_User;
            $no++;
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
