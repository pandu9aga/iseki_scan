<?php

namespace App\Http\Controllers\Helper;

use App\Http\Controllers\Controller;
use App\Models\SumMismatch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SumController extends Controller
{
    /**
     * Display the Sum (Part Sum Not Match) view based on the current user role layout.
     */
    public function index()
    {
        $layout = 'layouts.user'; // Default for member

        if (session()->has('Id_User')) {
            $typeUser = session('Id_Type_User');
            if ($typeUser == 2) {
                $layout = 'layouts.main'; // Admin
            } elseif ($typeUser == 1) {
                $layout = 'layouts.mc'; // Mc
            } elseif ($typeUser == 4) {
                $layout = 'layouts.area'; // Area
            }
        }

        return view('helpers.sum', compact('layout'));
    }

    /**
     * Return datatables data for sum mismatches.
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $query = SumMismatch::with(['rack', 'reporter', 'reporterUser', 'records' => function ($q) {
                $q->orderByDesc('Day_Record')->orderByDesc('Time_Record');
            }])
                ->orderByDesc('Id_Sum_Mismatch');

            if ($codeRack = $request->input('codeRack')) {
                $query->where('Code_Rack', 'LIKE', '%'.$codeRack.'%');
            }

            if ($status = $request->input('status')) {
                $query->where('Status', $status);
            }

            if ($keyword = $request->input('keyword')) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('Code_Rack', 'LIKE', "%$keyword%")
                        ->orWhere('Code_Item_Rack', 'LIKE', "%$keyword%")
                        ->orWhereHas('rack', function ($q2) use ($keyword) {
                            $q2->where('Name_Item_Rack', 'LIKE', "%$keyword%");
                        })
                        ->orWhereHas('reporter', function ($q2) use ($keyword) {
                            $q2->where('Name_Member', 'LIKE', "%$keyword%");
                        })
                        ->orWhereHas('reporterUser', function ($q2) use ($keyword) {
                            $q2->where('Username_User', 'LIKE', "%$keyword%");
                        });
                });
            }

            $isMc = session('Id_Type_User') == 1;

            return DataTables::eloquent($query)
                ->addColumn('Status_Badge', function ($m) {
                    $map = [
                        'open' => ['OPEN', 'warning'],
                        'ready' => ['READY', 'info'],
                        'closed' => ['CLOSED', 'success'],
                        'cancelled' => ['CANCELLED', 'secondary'],
                    ];
                    [$label, $class] = $map[$m->Status] ?? [strtoupper((string) $m->Status), 'secondary'];

                    return '<span class="badge badge-'.$class.'">'.$label.'</span>';
                })
                ->addColumn('Name_Part', function ($m) {
                    return optional($m->rack)->Name_Item_Rack ?? '-';
                })
                ->addColumn('Sum_Record', function ($m) {
                    $total = $m->records->sum('Sum_Record');

                    return $total ? $total : '-';
                })
                ->addColumn('Record_DST', function ($m) {
                    $record = $m->records->first();

                    if (! $record) {
                        return '<span class="text-muted">(menunggu record dst)</span>';
                    }

                    return Carbon::parse($record->Day_Record.' '.$record->Time_Record)->format('Y-m-d H:i:s');
                })
                ->addColumn('Reporter', function ($m) {
                    if ($m->reporter) {
                        return $m->reporter->Name_Member;
                    }
                    if ($m->reporterUser) {
                        return $m->reporterUser->Username_User;
                    }

                    return '-';
                })
                ->addColumn('Action', function ($m) use ($isMc) {
                    $html = '';
                    $token = csrf_token();

                    if ($m->Status === 'open' && $isMc) {
                        $html .= '<form method="POST" action="'.route('sum.ready', $m->Id_Sum_Mismatch).'" class="d-inline" onsubmit="return confirm(\'Tandai sisa barang sudah lengkap / SELESAI KIRIM?\');">'
                            .'<input type="hidden" name="_token" value="'.$token.'">'
                            .'<button class="btn btn-sm btn-info">Selesai Kirim</button></form> ';
                    }

                    return $html ?: '-';
                })
                ->rawColumns(['Status_Badge', 'Record_DST', 'Action'])
                ->make(true);
        }

        return abort(403, 'Unauthorized action.');
    }

    /**
     * MC menandai bahwa seluruh sisa kekurangan barang sudah lengkap.
     * Status langsung CLOSED (tidak lagi lewat status READY).
     */
    public function ready($id)
    {
        $mismatch = SumMismatch::find($id);
        if (! $mismatch) {
            return redirect()->back()->with('error', 'Data sum mismatch tidak ditemukan.');
        }

        if (session('Id_Type_User') != 1) {
            return redirect()->back()->with('error', 'Hanya akun MC yang bisa menandai selesai kirim.');
        }

        if ($mismatch->Status !== 'open') {
            return redirect()->back()->with('error', 'Status bukan OPEN, tidak bisa ditandai selesai kirim.');
        }

        $mismatch->delete();

        return redirect()->back()->with('success', 'Rack '.$mismatch->Code_Rack.' ditandai SELESAI KIRIM. Part Sum Not Match ditutup.');
    }

    /**
     * Batalkan laporan sum mismatch (oleh reporter/admin/MC).
     */
    public function cancel($id)
    {
        $mismatch = SumMismatch::find($id);
        if (! $mismatch) {
            return redirect()->back()->with('error', 'Data sum mismatch tidak ditemukan.');
        }

        if (! in_array($mismatch->Status, ['open', 'ready'])) {
            return redirect()->back()->with('error', 'Status tidak bisa dibatalkan.');
        }

        $reporterAllowed = session('Id_Member') && session('Id_Member') == $mismatch->Reported_By;
        $privileged = in_array(session('Id_Type_User'), [1, 2]);
        if (! $reporterAllowed && ! $privileged) {
            return redirect()->back()->with('error', 'Anda tidak berhak membatalkan laporan ini.');
        }

        $mismatch->update([
            'Status' => 'cancelled',
            'Resolved_At' => Carbon::now()->format('Y-m-d H:i:s'),
            'Updated_By' => session('Id_User') ?? session('Id_Member'),
            'Updated_At_Sum' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Laporan sum mismatch dibatalkan.');
    }
}