<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_number', 'user_id', 'cart_token', 'sub_total','total',
        'status', 'paid_at', 'tax', 'discount'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function address()
    {
        return $this->hasOne(OrderAddress::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
{
    return $this->hasMany(Payment::class);
}

}