<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Validation;
use App\Models\Request as RequestModel; // alias penting!
use App\Models\Rack;
use App\Models\Member;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ValidationController extends Controller
{
    public function index()
    {
        $date = Carbon::today();
        $dateForInput = $date->format('Y-m-d');
        $memberId = request('Id_User');

        $query = Validation::whereDate('Day_Validation', $date)
            ->with('user', 'request', 'rack')
            ->orderBy('Time_Validation', 'desc');

        if ($memberId) {
            $query->whereHas('request', function ($q) use ($memberId) {
                $q->where('Id_User', $memberId);
            });
        }

        $validations = $query->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalValidation = $validations->count();

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->orderBy('Name_Member')->get();

        return view('admins.validations.index', compact(
            'validations', 'totalValidation', 'formattedDate', 'date', 'dateForInput', 'members'
        ));
    }

    public function submit(Request $request)
    {
        $date = $request->input('Day_Validation');
        $dateForInput = Carbon::parse($date)->format('Y-m-d');
        $memberId = request('Id_User');

        $query = Validation::whereDate('Day_Validation', $date)
            ->with('user', 'request', 'rack')
            ->orderBy('Time_Validation', 'desc');

        if ($memberId) {
            $query->whereHas('request', function ($q) use ($memberId) {
                $q->where('Id_User', $memberId);
            });
        }

        $validations = $query->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalValidation = $validations->count();

        $members = Member::orderBy('Name_Member')->get();

        return view('admins.validations.index', compact(
            'validations', 'totalValidation', 'formattedDate', 'date', 'dateForInput', 'members'
        ));
    }

    public function export(Request $request)
    {
        $date = Carbon::parse($request->input('Day_Validation_Hidden'))->format('Y-m-d');
        $userId = $request->input('Id_User');

        // Ambil semua validation + relasi
        $query = Validation::whereDate('Day_Validation', $date)
            ->with([
                'request:Id_Request,Day_Request,Time_Request,Area_Request,Sum_Request,Urgent_Request,Id_User',
                'request.member:Id_Member,Name_Member',
                'rack:Code_Rack,Name_Item_Rack',
            ]);

        if ($userId) {
            $query->whereHas('request', function ($q) use ($userId) {
                $q->where('Id_User', $userId);
            });
        }

        $validations = $query->get();

        // === SORT SEMUA DATA SESUAI KRITERIA ===
        $validations = $validations->sortBy([
            ['request.Id_User', 'asc'],
            ['request.Urgent_Request', 'desc'],
            ['request.Area_Request', 'asc'],
            ['request.Time_Request', 'asc'],
        ])->values(); // reset index

        // === EXCEL ===
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'No', 'Time Validation', 'Area', 'Rack', 'Item', 'Name',
            'Time Request', 'Sum Request', 'Urgency', 'Member Request'
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        $sheet->setAutoFilter('A1:J1');

        $row = 2;
        $lastUserId = null; // mulai dengan null
        $no = 1;

        foreach ($validations as $val) {
            $currentUserId = $val->request?->Id_User;

            // Jika USER BERUBAH (termasuk null → user atau user → null), dan bukan baris pertama
            if ($lastUserId !== $currentUserId && $row > 2) {
                $sheet->fromArray(array_fill(0, 10, '-'), null, 'A' . $row);
                $row++;
                $no = 1; // reset nomor
            }

            // --- isi data ---
            $timeValidation = $val->Day_Validation . ' ' . $val->Time_Validation;
            $area = $val->request?->Area_Request ?? '';
            $rack = $val->Code_Rack;
            $item = $val->Code_Item_Rack;
            $name = $val->rack?->Name_Item_Rack ?? '';
            $timeRequest = '';
            $sumRequest = '';
            $urgency = '';
            $memberRequest = '';

            if ($val->request) {
                $timeRequest = ($val->request->Day_Request ?? '') . ' ' . ($val->request->Time_Request ?? '');
                $sumRequest = $val->request->Sum_Request ?? '';
                $urgency = $val->request->Urgent_Request == 1 ? '✓' : '';
                $memberRequest = $val->request->member?->Name_Member ?? '';
            }

            $sheet->fromArray([
                $no,
                $timeValidation,
                $area,
                $rack,
                $item,
                $name,
                $timeRequest,
                $sumRequest,
                $urgency,
                $memberRequest
            ], null, 'A' . $row);

            $lastUserId = $currentUserId;
            $no++;
            $row++;
        }

        // Auto-size kolom
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan & download
        $fileName = "Validation_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}