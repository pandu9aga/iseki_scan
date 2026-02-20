<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mistake extends Model
{
    use HasFactory;

    protected $table = 'mistakes';
    protected $primaryKey = 'Id_Mistake';
    public $timestamps = false;

    protected $fillable = [
        'Id_Request',
        'PIC',
        'Category_Mistake',
        'Manual_Category_Detail',
        'Day_Mistake',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class, 'Id_Request', 'Id_Request');
    }
}
