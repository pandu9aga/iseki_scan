<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Request as RequestModel;
use App\Models\Member;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SubmissionController extends Controller
{
    public function index()
    {
        $date = Carbon::today();
        $dateForInput = $date->format('Y-m-d');  // Untuk input date di view
        $submissions = RequestModel::whereDate('Day_Request', $date)->with('member')->where('Id_User', session('Id_Member'))->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');

        $totalSubmissions = $submissions->count();
        $correct = $submissions->where('Correctness_Request', 1)->count();
        $incorrect = $totalSubmissions - $correct;

        return view('users.submissions.index', compact('submissions', 'totalSubmissions', 'correct', 'incorrect', 'formattedDate', 'dateForInput'));
    }

    public function submit(Request $request)
    {
        $date = $request->input('Day_Request');
        $dateForInput = Carbon::parse($date)->format('Y-m-d');
        $submissions = RequestModel::whereDate('Day_Request', $date)->with('member')->where('Id_User', session('Id_Member'))->get();
        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');

        $totalSubmissions = $submissions->count();
        $correct = $submissions->where('Correctness_Request', 1)->count();
        $incorrect = $totalSubmissions - $correct;

        return view('users.submissions.index', compact(
            'submissions', 'totalSubmissions', 'correct', 'incorrect', 'formattedDate', 'dateForInput'
        ));
    }

    public function export(Request $request)
    {
        $date = $request->input('Day_Request_Hidden');
        $date = Carbon::parse($date)->format('Y-m-d');
        $submissions = RequestModel::whereDate('Day_Request', $date)->where('Id_User', session('Id_Member'))->with('member')->get();
        $name = Member::where('Id_Member', session('Id_Member'))->value('Name_Member');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = ['No', 'Date', 'Time', 'Item', 'Rack', 'Person', 'Sum Request'];
        $sheet->fromArray([$headers], null, 'A1');

        // Header style
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
        ]);

        $row = 2;
        foreach ($submissions as $index => $submission) {

            $sheet->fromArray([
                $index + 1,
                $date,
                $submission->Time_Request,
                $submission->Code_Item_Rack,
                $submission->Code_Rack,
                $submission->member->Name_Member ?? '-',
                $submission->Sum_Request,
            ], null, 'A' . $row);

            $row++;
        }

        $fileName = "Request_" . $name . "_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
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

    
}
