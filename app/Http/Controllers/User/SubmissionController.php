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

        $submissions = $query->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');

        $totalSubmissions = $submissions->count();
        $correct = $submissions->where('Correctness_Request', 1)->count();
        $incorrect = $totalSubmissions - $correct;

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->orderBy('Name_Member')->get();

        return view('users.submissions.index', compact(
            'submissions', 'totalSubmissions', 'correct', 'incorrect', 'formattedDate', 'dateForInput', 'members'
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

        $submissions = $query->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');

        $totalSubmissions = $submissions->count();
        $correct = $submissions->where('Correctness_Request', 1)->count();
        $incorrect = $totalSubmissions - $correct;

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->orderBy('Name_Member')->get();

        return view('users.submissions.index', compact(
            'submissions', 'totalSubmissions', 'correct', 'incorrect', 'formattedDate', 'dateForInput', 'members'
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

        $submissions = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = ['No', 'Time Request', 'Area', 'Rack', 'Sum Request', 'Urgenity', 'Item', 'Name', "1=Ready,2=Ship,\n3=Prod,4=Design", 'Time Record', 'Sum Record', 'Member Request', 'Member Record', 'Updated'];
        $sheet->fromArray([$headers], null, 'A1');

        // Header style
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
        ]);
        $sheet->getStyle('A1:N1')->getAlignment()->setWrapText(true);

        $row = 2;
        $lastUser = null;
        $no = 1;

        foreach ($submissions as $index => $submission) {
            
            // Reset nomor & kasih spasi kalau ganti user
            if ($lastUser !== null && $lastUser != $submission->Id_User) {
                $sheet->fromArray(
                    array_fill(0, 12, '-'), // 12 kolom sesuai header
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
            
            $sheet->fromArray([
                $no,
                $timeRequest,
                $submission->Area_Request ?? '',
                $submission->Code_Rack,
                $submission->Sum_Request,
                $submission->Urgent_Request == 1 ? '✓' : '',
                $submission->Code_Item_Rack,
                $submission->rack->Name_Item_Rack ?? '',
                $readyStockDisplay,
                $timeRecord,
                optional($submission->record)->Sum_Record ?? '',
                $submission->member->Name_Member ?? '',
                optional($submission->record)->member->Name_Member ?? '-',
                $submission->Updated_At_Request,
            ], null, 'A' . $row);

            $lastUser = $submission->Id_User;
            $row++;
            $no++;
        }

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
                ->rawColumns(['Urgent_Request', 'ready_status_display'])
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