<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Forgot;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserForgotController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month);
        $daysInMonth = $date->daysInMonth;

        $forgots = Forgot::with(['request.member'])
            ->whereMonth('Day_Forgot', $date->month)
            ->whereYear('Day_Forgot', $date->year)
            ->get();

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->get();

        $reportData = [];
        foreach ($members as $member) {
            $memberForgots = $forgots->filter(function ($f) use ($member) {
                return $f->PIC === $member->Name_Member;
            });

            $days = array_fill(1, $daysInMonth, 0);
            foreach ($memberForgots as $f) {
                $day = (int) Carbon::parse($f->Day_Forgot)->format('d');
                $days[$day]++;
            }

            $reportData[$member->Id_Member] = [
                'name' => $member->Name_Member,
                'total' => $memberForgots->count(),
                'days' => $days
            ];
        }

        // Sort by total descending
        uasort($reportData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        // Prepare chart data (Forgots per member per day - Cumulative)
        $chartData = [];
        $isCurrentMonth = ($date->year == Carbon::now()->year && $date->month == Carbon::now()->month);
        $currentDay = Carbon::now()->day;

        foreach ($members as $member) {
            $memberDays = array_fill(1, $daysInMonth, 0);
            $memberForgots = $forgots->filter(function ($f) use ($member) {
                return $f->PIC === $member->Name_Member;
            });

            if ($memberForgots->count() > 0) {
                foreach ($memberForgots as $f) {
                    $day = (int) Carbon::parse($f->Day_Forgot)->format('d');
                    $memberDays[$day]++;
                }

                $cumulative = 0;
                $accumulatedData = [];
                for ($i = 1; $i <= $daysInMonth; $i++) {
                    if ($isCurrentMonth && $i > $currentDay) {
                        $accumulatedData[] = null;
                    } else {
                        $cumulative += $memberDays[$i];
                        $accumulatedData[] = $cumulative;
                    }
                }

                $chartData[] = [
                    'label' => $member->Name_Member . " (" . $memberForgots->count() . ")",
                    'data' => $accumulatedData
                ];
            }
        }

        // Prepare Daily Total chart data (Daily sums across all members)
        $dailyTotalData = array_fill(1, $daysInMonth, 0);
        foreach ($forgots as $f) {
            $day = (int) Carbon::parse($f->Day_Forgot)->format('d');
            $dailyTotalData[$day]++;
        }

        return view('users.forgots.index', compact('reportData', 'month', 'daysInMonth', 'chartData', 'dailyTotalData'));
    }
}
