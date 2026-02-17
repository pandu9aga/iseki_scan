<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'users'; // Nama tabel
    protected $primaryKey = 'Id_User'; // Nama primary key
    protected $fillable = [
        'Name_User',
        'Username_User',
        'Password_User',
        'Id_Type_User',
        'Status_Non_Active'
    ];

    public $timestamps = false; // Jika tabel tidak memiliki created_at dan updated_at
}
