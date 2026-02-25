<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mistake;
use App\Models\Member;
use Carbon\Carbon;

class McMistakeController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month);
        $daysInMonth = $date->daysInMonth;

        $mistakes = Mistake::with(['request.member'])
            ->whereMonth('Day_Mistake', $date->month)
            ->whereYear('Day_Mistake', $date->year)
            ->get();

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->get();
        $categories = ['telat request', 'telat supply', 'telat supply mc', 'shipping', 'perubahan desain', 'lain-lain'];

        $reportData = [];
        foreach ($categories as $cat) {
            $catData = [];
            foreach ($members as $member) {
                $memberMistakes = $mistakes->filter(function ($m) use ($member, $cat) {
                    return $m->Category_Mistake === $cat && $m->PIC === $member->Name_Member;
                });

                $days = array_fill(1, $daysInMonth, 0);
                foreach ($memberMistakes as $m) {
                    $day = (int) Carbon::parse($m->Day_Mistake)->format('d');
                    $days[$day]++;
                }

                $catData[$member->Id_Member] = [
                    'name' => $member->Name_Member,
                    'total' => $memberMistakes->count(),
                    'days' => $days
                ];
            }
            
            uasort($catData, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });
            
            $reportData[$cat] = $catData;
        }

        $chartData = [];
        $isCurrentMonth = ($date->year == Carbon::now()->year && $date->month == Carbon::now()->month);
        $currentDay = Carbon::now()->day;

        foreach ($members as $member) {
            $memberDays = array_fill(1, $daysInMonth, 0);
            $memberMistakes = $mistakes->filter(function ($m) use ($member) {
                return $m->PIC === $member->Name_Member;
            });

            if ($memberMistakes->count() > 0) {
                foreach ($memberMistakes as $m) {
                    $day = (int) Carbon::parse($m->Day_Mistake)->format('d');
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
                    'label' => $member->Name_Member . " (" . $memberMistakes->count() . ")",
                    'data' => $accumulatedData
                ];
            }
        }

        $dailyTotalData = array_fill(1, $daysInMonth, 0);
        foreach ($mistakes as $m) {
            $day = (int) Carbon::parse($m->Day_Mistake)->format('d');
            $dailyTotalData[$day]++;
        }

        return view('mcs.mistakes.index', compact('reportData', 'month', 'categories', 'daysInMonth', 'chartData', 'dailyTotalData'));
    }
}
