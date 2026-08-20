<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;

class SumHistoryController extends Controller
{
    /**
     * Display the sum history page for admin.
     * Rangkuman selisih antara Sum_Request dan Sum_Record per item, difilter per tanggal record.
     */
    public function index(Request $request)
    {
        $today = $request->input('date', Carbon::today()->format('Y-m-d'));
        $month = $request->input('month', '');

        // If month is provided, we pass it to getStats, otherwise we pass date
        if ($month) {
            $stats = $this->getStats(null, $month);
        } else {
            $stats = $this->getStats($today);
        }

        return view('admins.sum_history.index', compact('stats', 'today', 'month'));
    }

    /**
     * Return datatables data: records dengan selisih Sum_Record vs Sum_Request.
     */
    public function getData(Request $request)
    {
        if (! $request->ajax()) {
            return abort(403, 'Unauthorized action.');
        }

        $query = $this->baseQuery($request);

        return DataTables::of($query)
            ->addColumn('Rack_Name', function ($r) {
                return $r->Rack_Name ?? '-';
            })
            ->addColumn('Selisih', function ($r) {
                return (int) $r->Sum_Record - (int) $r->Sum_Request;
            })
            ->orderColumn('rec.Day_Record', 'rec.Day_Record $1, rec.Time_Record $1, rec.Id_Record $1')
            ->make(true);
    }

    /**
     * Export data selisih (sesuai filter aktif: tanggal/bulan + range selisih) ke Excel.
     */
    public function export(Request $request)
    {
        $query = $this->baseQuery($request)
            ->orderByDesc('rec.Day_Record')
            ->orderByDesc('rec.Time_Record')
            ->orderByDesc('rec.Id_Record')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['No', 'Tgl Record', 'Tgl Request', 'Rack', 'Item Code', 'Nama Item', 'Sum Request', 'Sum Record', 'Selisih'];
        $sheet->fromArray([$headers], null, 'A1');

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4F4F4F');
        $sheet->getStyle('A1:I1')->getFont()->getColor()->setRGB('FFFFFF');

        $row = 2;
        $no = 1;
        foreach ($query as $r) {
            $selisih = (int) $r->Sum_Record - (int) $r->Sum_Request;
            $sheet->setCellValueExplicit('A'.$row, $no, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValue('B'.$row, trim($r->Day_Record.' '.($r->Time_Record ?? '')));
            $sheet->setCellValue('C'.$row, trim($r->Day_Request.' '.($r->Time_Request ?? '')));
            $sheet->setCellValue('D'.$row, $r->Code_Rack);
            $sheet->setCellValue('E'.$row, $r->Code_Item_Rack);
            $sheet->setCellValue('F'.$row, $r->Rack_Name ?? '-');
            $sheet->setCellValue('G'.$row, $r->Sum_Request);
            $sheet->setCellValue('H'.$row, $r->Sum_Record);
            $sheet->setCellValue('I'.$row, $selisih);
            $row++;
            $no++;
        }

        foreach (['A','B','C','D','E','F','G','H','I'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Sum_History_'.Carbon::now()->format('Ymd_His').'.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$fileName.'"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Query dasar data selisih dengan filter tanggal/bulan dan range minus/surplus.
     * minus_selisih = ukuran minus (tampil selisih -1 s/d -minus), surplus_selisih = ukuran surplus (tampil +1 s/d +surplus).
     */
    private function baseQuery($request)
    {
        $query = DB::table('records as rec')
            ->join('requests as req', 'rec.Id_Request', '=', 'req.Id_Request')
            ->leftJoin('racks as rack', 'req.Code_Rack', '=', 'rack.Code_Rack')
            ->select(
                'rec.Id_Record',
                'req.Id_Request',
                'rec.Day_Record',
                'rec.Time_Record',
                'req.Day_Request',
                'req.Time_Request',
                'req.Code_Item_Rack',
                'req.Code_Rack',
                'req.Sum_Request',
                'rec.Sum_Record',
                'rack.Name_Item_Rack as Rack_Name'
            )
            ->whereNotNull('rec.Id_Request')
            ->whereRaw('(rec.Sum_Record - req.Sum_Request) != 0');

        if ($request->filled('month')) {
            $month = $request->input('month');
            $query->whereRaw('DATE_FORMAT(rec.Day_Record, "%Y-%m") = ?', [$month]);
        } else {
            $date = $request->input('date', Carbon::today()->format('Y-m-d'));
            $query->where('rec.Day_Record', $date);
        }

        $minus = $request->input('minus_selisih');
        $surplus = $request->input('surplus_selisih');

        $query->where(function ($q) use ($minus, $surplus) {
            // Minus (datang lebih sedikit): tampilkan selisih -1 s/d -minus
            if ($minus !== null && $minus !== '' && (int) $minus > 0) {
                $q->orWhereRaw('(rec.Sum_Record - req.Sum_Request) >= ? AND (rec.Sum_Record - req.Sum_Request) <= -1', [-(int) $minus]);
            }
            // Surplus (datang lebih banyak): tampilkan selisih +1 s/d +surplus
            if ($surplus !== null && $surplus !== '' && (int) $surplus > 0) {
                $q->orWhereRaw('(rec.Sum_Record - req.Sum_Request) >= 1 AND (rec.Sum_Record - req.Sum_Request) <= ?', [(int) $surplus]);
            }
        });

        return $query;
    }

    /**
     * Hitung stats untuk summary cards berdasarkan tanggal record.
     */
    private function getStats($date = null, $month = null)
    {
        $base = DB::table('records as rec')
            ->join('requests as req', 'rec.Id_Request', '=', 'req.Id_Request')
            ->whereNotNull('rec.Id_Request')
            ->whereRaw('ABS(rec.Sum_Record - req.Sum_Request) >= 10');

        if ($month) {
            $base->whereRaw('DATE_FORMAT(rec.Day_Record, "%Y-%m") = ?', [$month]);
        } else {
            $date = $date ?? Carbon::today()->format('Y-m-d');
            $base->where('rec.Day_Record', $date);
        }

        $s10 = (clone $base)->count();
        $s25 = (clone $base)->whereRaw('ABS(rec.Sum_Record - req.Sum_Request) >= 25')->count();
        $s50 = (clone $base)->whereRaw('ABS(rec.Sum_Record - req.Sum_Request) >= 50')->count();

        $lebih_10 = (clone $base)->whereRaw('(rec.Sum_Record - req.Sum_Request) >= 10')->count();
        $kurang_10 = (clone $base)->whereRaw('(rec.Sum_Record - req.Sum_Request) <= -10')->count();
        $lebih_25 = (clone $base)->whereRaw('(rec.Sum_Record - req.Sum_Request) >= 25')->count();
        $kurang_25 = (clone $base)->whereRaw('(rec.Sum_Record - req.Sum_Request) <= -25')->count();
        $lebih_50 = (clone $base)->whereRaw('(rec.Sum_Record - req.Sum_Request) >= 50')->count();
        $kurang_50 = (clone $base)->whereRaw('(rec.Sum_Record - req.Sum_Request) <= -50')->count();

        return [
            's10' => ['total' => $s10, 'lebih' => $lebih_10, 'kurang' => $kurang_10],
            's25' => ['total' => $s25, 'lebih' => $lebih_25, 'kurang' => $kurang_25],
            's50' => ['total' => $s50, 'lebih' => $lebih_50, 'kurang' => $kurang_50],
        ];
    }
}