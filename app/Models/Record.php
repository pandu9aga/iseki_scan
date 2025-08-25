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
    ];

    // Relasi ke model User
    public function member()
    {
        return $this->belongsTo(Member::class, 'Id_User', 'Id_Member');
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'Id_Request', 'Id_Request');
    }
}
