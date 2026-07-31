<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    protected $table = 'withdrawals';
    protected $primaryKey = 'Id_Withdrawal';
    public $timestamps = false;

    protected $fillable = [
        'Name_Withdrawal',
        'Date_Withdrawal',
        'Code_Item_Withdrawal',
        'Oke_Withdrawal',
        'Date_Oke_Withdrawal',
        'NIK_Withdrawal',
        'Arrive_Qc',
        'Date_Arrive_Qc',
        'Oke_Receiving',
        'Date_Receiving',
        'Finish_Receiving',
        'Date_Finish_Receiving',
        'NIK_Return',
        'Code_Rack_Return',
        'Date_Return',
        'Is_User',
        'Desc_Finish',
    ];

    protected $casts = [
        'Date_Withdrawal' => 'datetime',
        'Oke_Withdrawal' => 'boolean',
        'Date_Oke_Withdrawal' => 'datetime',
        'Arrive_Qc' => 'boolean',
        'Date_Arrive_Qc' => 'datetime',
        'Oke_Receiving' => 'boolean',
        'Date_Receiving' => 'datetime',
        'Finish_Receiving' => 'boolean',
        'Date_Finish_Receiving' => 'datetime',
        'Date_Return' => 'datetime',
    ];

    public function rack()
    {
        return $this->belongsTo(Rack::class, 'Code_Item_Withdrawal', 'Code_Item_Rack');
    }
}
