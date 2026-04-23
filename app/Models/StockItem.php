<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    use HasFactory;

    protected $table = 'stock_items';
    protected $primaryKey = 'Id_Stock_Item';

    public $timestamps = false;

    protected $fillable = [
        'Code_Rack_Stock_Item',
    ];
}
