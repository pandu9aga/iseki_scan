<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items'; // Nama tabel
    protected $primaryKey = 'Id_Item'; // Nama primary key

    public $timestamps = false; // Jika tabel tidak memiliki created_at dan updated_at
}
