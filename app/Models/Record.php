<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    use HasFactory;

    protected $table = 'records'; // Nama tabel
    protected $primaryKey = 'Id_Record'; // Nama primary key

    public $timestamps = false;

    protected $fillable = [
        'Day_Record',
        'Time_Record',
        'Code_Item_Rack',
        'Code_Rack',
        'Id_User',
        'Correctness_Record',
        'Sum_Record',
        'Id_Request',
        'Updated_At_Record',
        'Is_User',
    ];

    // Relasi ke model User
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

    public function request()
    {
        return $this->belongsTo(Request::class, 'Id_Request', 'Id_Request');
    }

    public function rack()
    {
        return $this->belongsTo(Rack::class, 'Code_Rack', 'Code_Rack');
    }
}
