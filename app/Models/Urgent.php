<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Urgent extends Model
{
    use HasFactory;

    protected $table = 'urgents'; // Nama tabel

    protected $primaryKey = 'Id_Urgent'; // Nama primary key

    public $timestamps = false; // Jika tabel tidak memiliki created_at dan updated_at

    protected $fillable = [
        'Id_Type_User',
        'Id_User',
        'Code_Rack',
        'Id_Request',
        'Id_Member',
        'Time_Urgent',
        'Id_Mistake',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'Id_Member', 'Id_Member');
    }

    // This could belong to User or Member depending on Id_User origin
    public function user()
    {
        return $this->belongsTo(User::class, 'Id_User', 'Id_User');
    }

    public function requestModel()
    {
        return $this->belongsTo(Request::class, 'Id_Request', 'Id_Request');
    }

    public function mistake()
    {
        return $this->belongsTo(Mistake::class, 'Id_Mistake', 'Id_Mistake');
    }
}
