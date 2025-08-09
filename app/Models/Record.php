<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    use HasFactory;

    protected $table = 'records'; // Nama tabel
    protected $primaryKey = 'Id_Record'; // Nama primary key

    public $timestamps = false; // Jika tabel tidak memiliki created_at dan updated_at

    // Relasi ke model User
    public function member()
    {
        return $this->belongsTo(Member::class, 'Id_User', 'Id_Member');
    }
}
