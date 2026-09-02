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
        'Actual_Submitted_At',
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
        'Is_User',
    ];

    // Relasi ke Member
    public function member()
    {
        return $this->belongsTo(Member::class, 'Id_User', 'Id_Member');
    }

    // Relasi ke User (admin)
    public function user()
    {
        return $this->belongsTo(User::class, 'Id_User', 'Id_User');
    }

    // Helper attribute for display name
    public function getDisplayNameAttribute()
    {
        if ($this->Is_User == 1) {
            return optional($this->user)->Name_User ?? 'Admin';
        }
        return optional($this->member)->Name_Member ?? '';
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
        $totalSeconds = $now->timestamp - $statusTime->timestamp;
        
        // Subtract non-working seconds (weekend, holiday, etc)
        $nonWorkingSeconds = 0;
        $tempTime = $statusTime->copy();
        while ($tempTime->lt($now)) {
            $nextHour = $tempTime->copy()->addHour();
            if ($nextHour->gt($now)) $nextHour = $now->copy();
            
            // Check using SpecialDate model
            if (! \App\Models\SpecialDate::isWorkday($tempTime)) {
                $nonWorkingSeconds += $nextHour->timestamp - $tempTime->timestamp;
            }
            $tempTime = $nextHour;
        }
        
        $workingSeconds = $totalSeconds - $nonWorkingSeconds;
        
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