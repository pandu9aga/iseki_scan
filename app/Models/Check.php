<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Check extends Model
{
    protected $table = 'checks';
    protected $primaryKey = 'Id_Checks';

    public $timestamps = false;

    protected $fillable = [
        'Time_Check',
        'Code_Rack',
        'Code_Item_Rack',
        'Id_User',
        'Status_Check',
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

    // Relasi ke Rack
    public function rack()
    {
        return $this->belongsTo(Rack::class, 'Code_Rack', 'Code_Rack');
    }

    // Helper attribute for display name
    public function getDisplayNameAttribute()
    {
        if ($this->Is_User == 1) {
            return optional($this->user)->Username_User ?? 'Admin';
        }
        return optional($this->member)->Name_Member ?? '-';
    }

    // Helper for status label
    public function getStatusLabelAttribute()
    {
        return $this->Status_Check == 1 ? 'Mid' : ($this->Status_Check == 2 ? 'Lot' : '-');
    }
}
