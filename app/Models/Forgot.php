<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Forgot extends Model
{
    use HasFactory;

    protected $table = 'forgots';
    protected $primaryKey = 'Id_Forgot';
    public $timestamps = false;

    protected $fillable = [
        'Id_Request',
        'PIC',
        'Day_Forgot',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class, 'Id_Request', 'Id_Request');
    }
}
