<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rack extends Model
{
    use HasFactory;

    protected $table = 'racks'; // Nama tabel
    protected $primaryKey = 'Id_Rack'; // Nama primary key

    public $timestamps = false; // Jika tabel tidak memiliki created_at dan updated_at

       protected $fillable = [
        'Code_Rack',
        'Code_Item_Rack',
        'Name_Item_Rack',
        'Type_Tractor_Rack',
        'Update_Rack'
    ];
}
