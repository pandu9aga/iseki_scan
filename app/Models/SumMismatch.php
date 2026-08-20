<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SumMismatch extends Model
{
    use HasFactory;

    protected $table = 'sum_mismatches';
    protected $primaryKey = 'Id_Sum_Mismatch';
    public $timestamps = false;

    protected $fillable = [
        'Id_Request',
        'Id_Record',
        'Code_Item_Rack',
        'Code_Rack',
        'Sum_Request',
        'Received_Qty',
        'Outstanding_Qty',
        'Status',
        'Time_Mismatch',
        'Ready_Date',
        'Reported_By',
        'Resolved_At',
        'Updated_By',
        'Updated_At_Sum',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class, 'Id_Request', 'Id_Request');
    }

    public function record()
    {
        return $this->belongsTo(Record::class, 'Id_Record', 'Id_Record');
    }

    public function records()
    {
        return $this->hasMany(Record::class, 'Id_Request', 'Id_Request');
    }

    public function rack()
    {
        return $this->belongsTo(Rack::class, 'Code_Rack', 'Code_Rack');
    }

    public function reporter()
    {
        return $this->belongsTo(Member::class, 'Reported_By', 'Id_Member');
    }

    public function reporterUser()
    {
        return $this->belongsTo(User::class, 'Reported_By', 'Id_User');
    }
}