<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class OrderAddress extends Model
{
    use HasFactory;
    protected $table = 'order_address';

    protected $fillable = [
        'order_id', 'full_name','phone', 'email',  'city', 'street', 'zip_code','country'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

}
