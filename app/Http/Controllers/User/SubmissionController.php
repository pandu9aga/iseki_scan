<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Request as RequestModel;
use App\Models\Record;
use App\Models\Member;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Yajra\DataTables\Facades\DataTables;

class SubmissionController extends Controller
{
    public function index()
    {
        $date = Carbon::today();
        $dateForInput = $date->format('Y-m-d');
        $memberIds = request('Id_User', []);

        $query = RequestModel::whereDate('Day_Request', $date)
            ->with('member', 'record', 'rack')
            ->orderBy('Time_Request', 'desc');

        if (!empty($memberIds)) {
            $query->whereIn('Id_User', $memberIds);
        }

        $statusFilter = request('statusFilter');
        if ($statusFilter) {
            switch ($statusFilter) {
                case 'ready':
                    $query->whereNotNull('Ready_Request');
                    break;
                case 'shipping':
                    $query->whereNotNull('Shipping_Request');
                    break;
                case 'production':
                    $query->whereNotNull('Production_Area_Request');
                    break;
                case 'design_change':
                    $query->whereNotNull('Design_Changes_Request');
                    break;
            }
        }

        $submissions = $query->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');

        $totalSubmissions = $submissions->count();
        $correct = $submissions->where('Correctness_Request', 1)->count();
        $incorrect = $totalSubmissions - $correct;

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->orderBy('Name_Member')->get();

        return view('users.submissions.index', compact(
            'submissions',
            'totalSubmissions',
            'correct',
            'incorrect',
            'formattedDate',
            'dateForInput',
            'members'
        ));
    }

    public function submit(Request $request)
    {
        $date = $request->input('Day_Request');
        $dateForInput = Carbon::parse($date)->format('Y-m-d');
        $memberIds = $request->input('Id_User', []);

        $query = RequestModel::whereDate('Day_Request', $date)
            ->with('member', 'record', 'rack')
            ->orderBy('Time_Request', 'desc');

        if (!empty($memberIds)) {
            $query->whereIn('Id_User', $memberIds);
        }

        $statusFilter = $request->input('statusFilter');
        if ($statusFilter) {
            switch ($statusFilter) {
                case 'ready':
                    $query->whereNotNull('Ready_Request');
                    break;
                case 'shipping':
                    $query->whereNotNull('Shipping_Request');
                    break;
                case 'production':
                    $query->whereNotNull('Production_Area_Request');
                    break;
                case 'design_change':
                    $query->whereNotNull('Design_Changes_Request');
                    break;
            }
        }

        $submissions = $query->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');

        $totalSubmissions = $submissions->count();
        $correct = $submissions->where('Correctness_Request', 1)->count();
        $incorrect = $totalSubmissions - $correct;

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->orderBy('Name_Member')->get();

        return view('users.submissions.index', compact(
            'submissions',
            'totalSubmissions',
            'correct',
            'incorrect',
            'formattedDate',
            'dateForInput',
            'members'
        ));
    }

    public function export(Request $request)
    {
        $date = Carbon::parse($request->input('Day_Request_Hidden'))->format('Y-m-d');
        $memberIds = $request->input('Id_User', []);

        $query = RequestModel::whereDate('Day_Request', $date)
            ->with('member', 'record', 'rack')
            ->orderBy('Id_User')
            ->orderBy('Urgent_Request', 'desc')
            ->orderBy('Area_Request')
            ->orderBy('Time_Request');

        if (!empty($memberIds)) {
            $query->whereIn('Id_User', $memberIds);
        }

        $statusFilter = $request->input('statusFilter');
        if ($statusFilter) {
            switch ($statusFilter) {
                case 'ready':
                    $query->whereNotNull('Ready_Request');
                    break;
                case 'shipping':
                    $query->whereNotNull('Shipping_Request');
                    break;
                case 'production':
                    $query->whereNotNull('Production_Area_Request');
                    break;
                case 'design_change':
                    $query->whereNotNull('Design_Changes_Request');
                    break;
            }
        }

        $submissions = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = ['No', 'Time Request', 'Area', 'Rack', 'Sum Request', 'Urgenity', 'Item', 'Name', "1=Ready,2=Ship,\n3=Prod,4=Design", 'Sum Stock', 'Ready Stock', 'Estimation Date', 'Time Record', 'Sum Record', 'Member Request', 'Member Record', 'Updated'];
        $sheet->fromArray([$headers], null, 'A1');

        // Header style
        $sheet->getStyle('A1:Q1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
        ]);
        $sheet->getStyle('A1:Q1')->getAlignment()->setWrapText(true);

        $row = 2;
        $lastUser = null;
        $no = 1;

        foreach ($submissions as $index => $submission) {

            // Reset nomor & kasih spasi kalau ganti user
            if ($lastUser !== null && $lastUser != $submission->Id_User) {
                $sheet->fromArray(
                    array_fill(0, 17, '-'), // 17 kolom sesuai header
                    null,
                    'A' . $row
                );
                $row++;
                $no = 1; // reset nomor
            }

            $timeRequest = ($submission->Day_Request ?? '') . " " . ($submission->Time_Request ?? '');
            $timeRecord = ($submission->record->Day_Record ?? '') . " " . ($submission->record->Time_Record ?? '');

            // Ready Status
            $readyDisplay = [];
            if ($submission->Ready_Request) $readyDisplay[] = 'Ready: ' . $submission->Ready_Request;
            if ($submission->Shipping_Request) $readyDisplay[] = 'Shipping: ' . $submission->Shipping_Request;
            if ($submission->Production_Area_Request) $readyDisplay[] = 'Production: ' . $submission->Production_Area_Request;
            if ($submission->Design_Changes_Request) $readyDisplay[] = 'Design: ' . $submission->Design_Changes_Request;
            $readyStockDisplay = implode(' | ', $readyDisplay);

            $statusCode = '';
            if ($submission->Ready_Request !== null) {
                $statusCode = '1';
            } elseif ($submission->Shipping_Request !== null) {
                $statusCode = '2';
            } elseif ($submission->Production_Area_Request !== null) {
                $statusCode = '3';
            } elseif ($submission->Design_Changes_Request !== null) {
                $statusCode = '4';
            }

            // Determine Sum Stock display value
            $sumStockDisplay = '';
            if ($statusCode == '2' || $statusCode == '4') {
                $sumStockDisplay = $submission->Stock_Shipping ?? '';
            } else {
                $sumStockDisplay = $submission->Sum_Stock ?? '';
            }

            $estimationDisplay = '';
            if ($submission->Estimation_Stock) {
                $estimationDisplay = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                    \Carbon\Carbon::parse($submission->Estimation_Stock)
                );
            }

            $sheet->fromArray([
                $no,
                $timeRequest,
                $submission->Area_Request ?? '',
                $submission->Code_Rack,
                $submission->Sum_Request,
                $submission->Urgent_Request == 1 ? '✓' : '',
                $submission->Code_Item_Rack,
                $submission->rack->Name_Item_Rack ?? '',
                $statusCode,
                $sumStockDisplay,
                $readyStockDisplay,
                $estimationDisplay,
                $timeRecord,
                optional($submission->record)->Sum_Record ?? '',
                $submission->Is_User == 1 ? (optional($submission->user)->Name_User ?? 'Admin') : ($submission->member->Name_Member ?? ''),
                optional($submission->record)->Is_User == 1 ? (optional($submission->record->user)->Name_User ?? 'Admin') : (optional($submission->record)->member->Name_Member ?? '-'),
                $submission->Updated_At_Request,
            ], null, 'A' . $row);

            // Set background color for readonly Sum Stock based on status
            if ($statusCode == '2') {
                $sheet->getStyle('J' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ADD8E6'] // Light Blue
                    ],
                    'font' => [
                        'color' => ['rgb' => '000000']
                    ]
                ]);
            } elseif ($statusCode == '4') {
                $sheet->getStyle('J' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFFE0'] // Light Yellow
                    ],
                    'font' => [
                        'color' => ['rgb' => '000000']
                    ]
                ]);
            }

            $lastUser = $submission->Id_User;
            $row++;
            $no++;
        }

        $sheet->getStyle('L2:L1000')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $validation = $sheet->getCell('L2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DATE);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Input Error');
        $validation->setError('Harus berupa tanggal!');
        $validation->setPromptTitle('Pilih Tanggal');
        $validation->setPrompt('Format: DD/MM/YYYY');
        $validation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_GREATERTHANOREQUAL);
        $validation->setFormula1(\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(\Carbon\Carbon::parse('1900-01-01')));
        $sheet->setDataValidation('L2:L1000', $validation);

        // 🔑 Auto size kolom
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "Request_Report_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function search()
    {
        if (request()->ajax()) {
            $query = RequestModel::with('member', 'record', 'rack');

            if ($statusFilter = request('statusFilter')) {
                switch ($statusFilter) {
                    case 'ready':
                        $query->whereNotNull('Ready_Request');
                        break;
                    case 'shipping':
                        $query->whereNotNull('Shipping_Request');
                        break;
                    case 'production':
                        $query->whereNotNull('Production_Area_Request');
                        break;
                    case 'design_change':
                        $query->whereNotNull('Design_Changes_Request');
                        break;
                }
            }

            return DataTables::eloquent($query)
                ->editColumn('Day_Request', function ($r) {
                    return $r->Day_Request . ' ' . $r->Time_Request;
                })
                ->addColumn('Urgent_Request', function ($r) {
                    return $r->Urgent_Request == 1 ? '✓' : '';
                })
                ->addColumn('Name', function ($r) {
                    return optional($r->rack)->Name_Item_Rack ?? '';
                })
                ->addColumn('Type_Tractor_Rack', function ($r) {
                    $type = optional($r->rack)->Type_Tractor_Rack ?? '-';
                    if ($type === '-') {
                        return '-';
                    }
                    $short = \Illuminate\Support\Str::limit($type, 20);
                    return '<span title="' . e($type) . '">' . e($short) . '</span>';
                })
                ->addColumn('Time_Record', function ($r) {
                    $day = optional($r->record)->Day_Record ?? '';
                    $time = optional($r->record)->Time_Record ?? '';
                    return trim("$day $time");
                })
                ->addColumn('Status_Request_Display', function ($r) {
                    $status = $r->Status_Request ?? '';
                    switch ($status) {
                        case 'Waiting':
                            return '<span class="badge badge-warning">Waiting</span>';
                        case 'Done':
                            return '<span class="badge badge-success">Done</span>';
                        default:
                            return '<span class="badge badge-secondary">' . e($status) . '</span>';
                    }
                })
                ->addColumn('Sum_Record', function ($r) {
                    return optional($r->record)->Sum_Record ?? '';
                })
                ->addColumn('Member_Request', function ($r) {
                    if ($r->Is_User == 1) {
                        return optional($r->user)->Name_User ?? 'Admin';
                    }
                    return optional($r->member)->Name_Member ?? '';
                })
                ->addColumn('Member_Record', function ($r) {
                    if (optional($r->record)->Is_User == 1) {
                        return optional($r->record->user)->Name_User ?? 'Admin';
                    }
                    return optional($r->record)?->member?->Name_Member ?? '';
                })
                ->editColumn('Updated_At_Request', function ($r) {
                    return $r->Updated_At_Request ?? '';
                })
                ->editColumn('Sum_Stock', function ($r) {
                    if ($r->Shipping_Request !== null || $r->Design_Changes_Request !== null) {
                        return $r->Stock_Shipping;
                    }
                    return $r->Sum_Stock;
                })
                ->addColumn('Estimation_Stock', function ($r) {
                    if ($r->Estimation_Stock) {
                        return \Carbon\Carbon::parse($r->Estimation_Stock)->format('d/m/Y');
                    }
                    return '';
                })
                ->addColumn('ready_status_display', function ($r) {
                    $statuses = [];
                    if ($r->Ready_Request) {
                        $statuses[] = '<span class="badge badge-success">Ready</span>: ' . $r->Ready_Request;
                    }
                    if ($r->Shipping_Request) {
                        $statuses[] = '<span class="badge badge-info">Shipping</span>: ' . $r->Shipping_Request;
                    }
                    if ($r->Production_Area_Request) {
                        $statuses[] = '<span class="badge badge-primary">Production</span>: ' . $r->Production_Area_Request;
                    }
                    if ($r->Design_Changes_Request) {
                        $statuses[] = '<span class="badge badge-warning">Design Change</span>: ' . $r->Design_Changes_Request;
                    }
                    return implode(' | ', $statuses);
                })
                ->filterColumn('Id_User', function ($query, $keyword) {
                    if ($keyword !== '') {
                        $query->where('Id_User', $keyword);
                    }
                })
                ->orderColumn('Day_Request', function ($query, $order) {
                    $query->orderBy('Day_Request', $order)->orderBy('Time_Request', $order);
                })
                ->orderColumn('ready_status_display', function ($query, $order) {
                    $query->orderByRaw('GREATEST(COALESCE(Ready_Request, "1000-01-01"), COALESCE(Shipping_Request, "1000-01-01"), COALESCE(Production_Area_Request, "1000-01-01"), COALESCE(Design_Changes_Request, "1000-01-01")) ' . $order);
                })
                ->rawColumns(['Urgent_Request', 'ready_status_display', 'Status_Request_Display', 'Type_Tractor_Rack'])
                ->make(true);
        }

        // Non-AJAX: kirim daftar member ke view
        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->orderBy('Name_Member')->get(['Id_Member', 'Name_Member']);
        return view('users.submissions.search', compact('members'));
    }

    public function reset(Request $request)
    {
        $date = $request->input('Day_Request');
        if (!$date) {
            return redirect()->back()->with('error', 'Date is required to reset data.');
        }

        RequestModel::whereDate('Day_Request', $date)->delete();

        return redirect()->route('submission')->with('success', "Submission data on {$date} has been reset.");
    }

    public function update(Request $request, $id)
    {
        $req = RequestModel::findOrFail($id);

        if (session('Id_Member') != $req->Id_User) {
            return redirect()->back()->with('error', 'You are not authorized to edit this request.');
        }

        $request->validate([
            'Sum_Request' => 'required|integer|min:1',
        ]);

        $req->Sum_Request = $request->Sum_Request;
        $req->Updated_At_Request = now(); // isi timestamp
        $req->save();

        return redirect()->back()->with('success', 'Request berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $submission = RequestModel::findOrFail($id);

        // Hapus record yang terkait (kalau ada)
        if ($submission->record) {
            $submission->record->delete();
        }

        // Hapus request
        $submission->delete();

        return redirect()->route('submission')->with('success', 'Request berhasil dihapus.');
    }
}
