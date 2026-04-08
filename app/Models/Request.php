<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $table = 'requests';
    protected $primaryKey = 'Id_Request';
    public $timestamps = false;

    protected $fillable = [
        'Day_Request',
        'Time_Request',
        'Code_Item_Rack',
        'Code_Rack',
        'Id_User',
        'Sum_Request',
        'Area_Request',
        'Urgent_Request',
        'Status_Request',
        'Status_Validation',
        'Ready_Request',
        'Shipping_Request',
        'Production_Area_Request',
        'Design_Changes_Request',
        'Sum_Stock',
        'Stock_Shipping',
        'Updated_At_Request',
    ];

    // Relasi ke Member
    public function member()
    {
        return $this->belongsTo(Member::class, 'Id_User', 'Id_Member');
    }

    public function record()
    {
        return $this->hasOne(Record::class, 'Id_Request', 'Id_Request');
    }

    public function rack()
    {
        return $this->belongsTo(Rack::class, 'Code_Rack', 'Code_Rack');
    }

    public function getWorkingDuration($statusTimestamp)
    {
        if (!$statusTimestamp) return null;

        $statusTime = \Carbon\Carbon::parse($statusTimestamp);
        $now = \Carbon\Carbon::now();

        if ($statusTime->gt($now)) {
            return [
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'total_seconds' => 0,
                'on_time' => true
            ];
        }

        // Hitung hari kerja (exclude Sat & Sun)
        $days = $statusTime->diffInDaysFiltered(function (\Carbon\Carbon $date) {
            return !$date->isWeekend();
        }, $now);

        // Untuk jam dan menit, kita ambil sisa dari diffInSeconds setelah dikurangi full days
        // Tapi diffInDaysFiltered menghitung hari penuh. 
        // Let's use a more precise way to get hours and minutes within those working days if needed.
        // However, the user request says: 
        // "nomor 1 1 harian karena sekarang 16-02-2026 09.52, karena sabtu minggu tidak dihitung"
        // 11-02-2026 (Wed) 16:15 to 16-02-2026 (Mon) 09:52.
        // Wed 16:15 to Thu 16:15 = 1 day
        // Thu 16:15 to Fri 16:15 = 2 days
        // Sat, Sun excluded.
        // Fri 16:15 to Mon 16:15 would be the 3rd working day.
        // Since it's Mon 09:52, it's 2 working days + some hours?
        // Wait, User says: "nomor 1 1 harian". 
        // 11-02-2026 16:15 (Wed) -> 13-02-2026 08:02 (Fri). 
        // Wed 16:15 to Thu 16:15 (1 day).
        // Thu 16:15 to Fri 08:02 (less than 1 day).
        // Total should be 1 day and X hours.
        // The user says "1 harian", probably meaning 1 day something.
        
        // Item 2: 11-02-2026 16:17 (Wed) to 12-02-2026 13:25 (Thu). 
        // This is less than 1 day if we talk about 24h periods.
        // But wait, the table in the screenshot shows:
        // 1. Ready: 2026-02-13 08:02:03. Overdue: 3 day(s) 1 hour(s) 48 minute(s) (Current time 16-02-2026 09:52)
        // 2. Ready: 2026-02-12 13:25:46. Overdue: 3 day(s) 20 hour(s) 24 minute(s)
        
        // Calculation from 2026-02-13 08:02 (Fri) to 2026-02-16 09:52 (Mon):
        // Calendar days: 3 days (Fri-Sat, Sat-Sun, Sun-Mon)
        // Working days: 1 day (Fri-Mon, excluding Sat/Sun).
        // So User wants 1 day.
        
        // Calculation from 2026-02-12 13:25 (Thu) to 2026-02-16 09:52 (Mon):
        // Calendar days: 4 days (Thu-Fri, Fri-Sat, Sat-Sun, Sun-Mon)
        // Working days: 2 days (Thu-Fri, Fri-Mon).
        // So User wants 2 days.

        $totalSeconds = $now->timestamp - $statusTime->timestamp;
        
        // Subtract weekend seconds
        $weekendSeconds = 0;
        $tempTime = $statusTime->copy();
        while ($tempTime->lt($now)) {
            $nextHour = $tempTime->copy()->addHour();
            if ($nextHour->gt($now)) $nextHour = $now->copy();
            
            if ($tempTime->isWeekend()) {
                $weekendSeconds += $nextHour->timestamp - $tempTime->timestamp;
            }
            $tempTime = $nextHour;
        }
        
        $workingSeconds = $totalSeconds - $weekendSeconds;
        
        if ($workingSeconds <= 0) {
            return [
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'total_seconds' => 0,
                'on_time' => true
            ];
        }

        $days = floor($workingSeconds / 86400);
        $hours = floor(($workingSeconds % 86400) / 3600);
        $minutes = floor(($workingSeconds % 3600) / 60);

        return [
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'total_seconds' => $workingSeconds,
            'on_time' => false
        ];
    }
}