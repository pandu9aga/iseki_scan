<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Request as RequestModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UserMissingController extends Controller
{
    public function oke_estimation()
    {
        $date = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        // Request yang sudah di-OK (Ok_Stock == 1) dan bertipe Shipping/Design Change
        $requests = RequestModel::with('member', 'record', 'rack')
            ->where(function ($query) {
                $query->whereNotNull('Shipping_Request')
                      ->orWhereNotNull('Design_Changes_Request');
            })
            ->whereNotNull('Estimation_Stock')
            ->where('Ok_Stock', 1)
            ->orderBy('Time_Ok_Stock', 'desc')
            ->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequests = $requests->count();

        return view('users.missings.oke_estimation', compact('requests', 'totalRequests', 'formattedDate', 'date'));
    }

    public function oke_estimation_export(Request $request)
    {
        $date = $request->input('Day_Request_Hidden', Carbon::today()->format('Y-m-d'));
        $date = Carbon::parse($date)->format('Y-m-d');

        $requests = RequestModel::with('member', 'record', 'rack')
            ->where(function ($query) {
                $query->whereNotNull('Shipping_Request')
                      ->orWhereNotNull('Design_Changes_Request');
            })
            ->whereNotNull('Estimation_Stock')
            ->where('Ok_Stock', 1)
            ->orderBy('Time_Ok_Stock', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'No', 'Rack', 'Item', 'Name', 'Sum', 'Status OK',
            'Time Request', 'Estimation Date', 'Stock Shipping',
            'Time OK', 'PIC'
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $row = 2;
        foreach ($requests as $index => $req) {
            $statusLabel = '';
            if ($req->Design_Changes_Request) {
                $statusLabel = 'Oke Perubahan Design';
            } elseif ($req->Shipping_Request) {
                $statusLabel = 'Oke Shipping';
            }

            $timeRequest = ($req->Day_Request ?? '') . " " . ($req->Time_Request ?? '');
            $estimationDate = '';
            if ($req->Estimation_Stock) {
                $estimationDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                    \Carbon\Carbon::parse($req->Estimation_Stock)
                );
            }

            $timeOk = $req->Time_Ok_Stock ? Carbon::parse($req->Time_Ok_Stock)->format('Y-m-d H:i:s') : '';

            $sheet->fromArray([
                $index + 1,
                $req->Code_Rack,
                $req->Code_Item_Rack,
                $req->rack->Name_Item_Rack ?? '',
                $req->Sum_Request,
                $statusLabel,
                $timeRequest,
                $estimationDate,
                $req->Stock_Shipping ?? '',
                $timeOk,
                $req->member->Name_Member ?? '-',
            ], null, 'A' . $row);

            $row++;
        }

        $sheet->getStyle('H2:H1000')->getNumberFormat()->setFormatCode('DD/MM/YYYY');

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "Oke_Estimation_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
