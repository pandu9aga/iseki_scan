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

class SubmissionController extends Controller
{
    public function index()
    {
        $date = Carbon::today();
        $dateForInput = $date->format('Y-m-d');  // Untuk input date di view
        $submissions = RequestModel::whereDate('Day_Request', $date)->with('member', 'record')->where('Id_User', session('Id_Member'))->orderBy('Time_Request', 'desc')->get();
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
        $submissions = RequestModel::whereDate('Day_Request', $date)->with('member', 'record')->where('Id_User', session('Id_Member'))->orderBy('Time_Request', 'desc')->get();
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
        $submissions = RequestModel::whereDate('Day_Request', $date)
            ->where('Id_User', session('Id_Member'))
            ->with('member', 'record')
            ->orderBy('Area_Request')
            ->get();

        $name = Member::where('Id_Member', session('Id_Member'))->value('Name_Member');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = ['No', 'Time Request', 'Time Record', 'Item', 'Area', 'Rack', 'Sum Request', 'Sum Record', 'Member', 'Updated'];
        $sheet->fromArray([$headers], null, 'A1');

        // Header style
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
        ]);

        $row = 2;
        foreach ($submissions as $index => $submission) {
            $timeRequest = ($submission->Day_Request ?? '') . " " . ($submission->Time_Request ?? '');
            $timeRecord = ($submission->record->Day_Record ?? '') . " " . ($submission->record->Time_Record ?? '');

            $sheet->fromArray([
                $index + 1,
                $timeRequest,
                $timeRecord,
                $submission->Code_Item_Rack,
                $submission->Area_Request ?? '',
                $submission->Code_Rack,
                $submission->Sum_Request,
                optional($submission->record)->Sum_Record ?? '',
                $submission->member->Name_Member ?? '-',
                $submission->Updated_At_Request,
            ], null, 'A' . $row);

            $row++;
        }

        // 🔑 Auto size kolom
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
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

    public function update(Request $request, $id)
    {
        $req = RequestModel::findOrFail($id);

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
