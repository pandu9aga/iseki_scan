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
}