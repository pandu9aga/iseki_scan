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
        'Id_User',
        'Code_Rack',
        'Id_Request',
        'Id_Member',
        'Time_Urgent',
    ];
}
