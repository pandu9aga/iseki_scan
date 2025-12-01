<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Validation extends Model
{
    use HasFactory;

    protected $table = 'validations';
    protected $primaryKey = 'Id_Validation';
    public $timestamps = false;

    protected $fillable = [
        'Day_Validation',
        'Time_Validation',
        'Code_Item_Rack',
        'Code_Rack',
        'Id_User',
        'Id_Request',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'Id_User', 'Id_User');
    }

    public function request()
    {
        return $this->hasOne(Request::class, 'Id_Request', 'Id_Request');
    }

    public function rack()
    {
        return $this->belongsTo(Rack::class, 'Code_Rack', 'Code_Rack');
    }
}