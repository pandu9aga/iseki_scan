<?php

namespace App\Http\Controllers\Helper;

use App\Http\Controllers\Controller;
use App\Models\Urgent;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UrgentController extends Controller
{
    /**
     * Display the urgents view based on the current user role layout.
     */
    public function index()
    {
        // Determine layout based on session Id_Type_User
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
        
        return view('helpers.urgents', compact('layout'));
    }

    /**
     * Return datatables data for urgents
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $query = Urgent::with(['member', 'user', 'requestModel']);

            // Custom Filter logic
            if ($codeRack = $request->input('codeRack')) {
                $query->where('Code_Rack', 'LIKE', '%' . $codeRack . '%');
            }

            if ($timeUrgent = $request->input('timeUrgent')) {
                $query->where('Time_Urgent', 'LIKE', '%' . $timeUrgent . '%');
            }

            return DataTables::eloquent($query)
                ->addColumn('PIC_Urgent', function ($urgent) {
                    return $urgent->member ? $urgent->member->Name_Member : '-';
                })
                ->addColumn('Request_Details', function ($urgent) {
                    if ($urgent->requestModel) {
                       return "Item: " . $urgent->requestModel->Code_Item_Rack . " - Sum: " . $urgent->requestModel->Sum_Request;
                    }
                    return 'N/A';
                })
                ->addColumn('Reporter', function ($urgent) {
                    // Sometimes Id_User comes from Member for old records? Let's check user table first
                    if ($urgent->user) {
                        return $urgent->user->Username_User;
                    } else {
                        // try member table if user doesn't exist
                        $member = \App\Models\Member::find($urgent->Id_User);
                        if ($member) {
                            return $member->Name_Member;
                        }
                    }
                    return $urgent->Id_User;
                })
                ->rawColumns(['PIC_Urgent', 'Request_Details', 'Reporter'])
                ->make(true);
        }

        return abort(403, 'Unauthorized action.');
    }
}
