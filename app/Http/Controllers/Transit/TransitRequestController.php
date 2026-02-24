<?php

namespace App\Http\Controllers\Transit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Request as RequestModel;
use App\Models\Member;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Yajra\DataTables\Facades\DataTables;

class TransitRequestController extends Controller
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

        $requests = $query->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequest = $requests->count();
        $correct = $requests->where('Correctness_Request', 1)->count();
        $incorrect = $totalRequest - $correct;

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->orderBy('Name_Member')->get();

        return view('transits.request', compact(
            'requests', 'totalRequest', 'correct', 'incorrect', 'formattedDate', 'date', 'dateForInput', 'members'
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

        $requests = $query->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequest = $requests->count();
        $correct = $requests->where('Correctness_Request', 1)->count();
        $incorrect = $totalRequest - $correct;

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->orderBy('Name_Member')->get();

        return view('transits.request', compact(
            'requests', 'totalRequest', 'correct', 'incorrect', 'formattedDate', 'date', 'dateForInput', 'members'
        ));
    }

    public function export(Request $request)
    {
        $date = Carbon::parse($request->input('Day_Request_Hidden', $request->input('Day_Request')))->format('Y-m-d');
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

        $requests = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'No', 'Time Request', 'Area', 'Rack', 'Sum Request', 'Urgenity', 'Item', 'Name',
            "1=Ready,2=Ship,\n3=Prod,4=Design", 'Ready Stock', 'Time Record', 'Sum Record', 'Member Request', 
            'Member Record', 'Updated', 'Id'
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:P1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1:P1')->getAlignment()->setWrapText(true);

        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $row = 2;
        $lastUser = null;
        $no = 1;

        foreach ($requests as $index => $requestItem) {
            if ($lastUser !== null && $lastUser != $requestItem->Id_User) {
                $sheet->fromArray(array_fill(0, 16, '-'), null, 'A' . $row);
                $row++;
                $no = 1;
            }

            $readyDisplay = [];
            if ($requestItem->Ready_Request) $readyDisplay[] = 'Ready: ' . $requestItem->Ready_Request;
            if ($requestItem->Shipping_Request) $readyDisplay[] = 'Shipping: ' . $requestItem->Shipping_Request;
            if ($requestItem->Production_Area_Request) $readyDisplay[] = 'Production: ' . $requestItem->Production_Area_Request;
            if ($requestItem->Design_Changes_Request) $readyDisplay[] = 'Design: ' . $requestItem->Design_Changes_Request;
            $readyStockDisplay = implode(' | ', $readyDisplay);

            $timeRequest = ($requestItem->Day_Request ?? '') . " " . ($requestItem->Time_Request ?? '');
            $timeRecord = ($requestItem->record->Day_Record ?? '') . " " . ($requestItem->record->Time_Record ?? '');

            $statusCode = '';
            if ($requestItem->Ready_Request !== null) $statusCode = '1';
            elseif ($requestItem->Shipping_Request !== null) $statusCode = '2';
            elseif ($requestItem->Production_Area_Request !== null) $statusCode = '3';
            elseif ($requestItem->Design_Changes_Request !== null) $statusCode = '4';

            $sheet->fromArray([
                $no,
                $timeRequest,
                $requestItem->Area_Request ?? '',
                $requestItem->Code_Rack,
                $requestItem->Sum_Request,
                $requestItem->Urgent_Request == 1 ? '✓' : '',
                $requestItem->Code_Item_Rack,
                $requestItem->rack->Name_Item_Rack ?? '',
                $statusCode,
                $readyStockDisplay,
                $timeRecord,
                optional($requestItem->record)->Sum_Record ?? '',
                $requestItem->member->Name_Member ?? '',
                optional($requestItem->record)->member->Name_Member ?? '',
                $requestItem->Updated_At_Request,
                $requestItem->Id_Request,
            ], null, 'A' . $row);

            $lastUser = $requestItem->Id_User;
            $no++;
            $row++;
        }

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "Transit_Request_" . $date . ".xlsx";
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
                    return optional($r->member)->Name_Member ?? '';
                })
                ->addColumn('Member_Record', function ($r) {
                    return optional($r->record)?->member?->Name_Member ?? '';
                })
                ->editColumn('Updated_At_Request', function ($r) {
                    return $r->Updated_At_Request ?? '';
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
                ->rawColumns(['Urgent_Request', 'ready_status_display', 'Status_Request_Display'])
                ->make(true);
        }

        // Non-AJAX: kirim daftar member ke view
        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->orderBy('Name_Member')->get(['Id_Member', 'Name_Member']);
        return view('transits.request_search', compact('members'));
    }
}
