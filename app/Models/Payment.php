<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'provider',
        'method',
        'amount',
        'status',
        'paymob_order_id',
        'paymob_token',
        'transaction_id',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'amount'       => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
