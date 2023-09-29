<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    use HasFactory;
    protected $table = 'orders_tbl';
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        'group_id',
        'order_id',
        'product_group_id',
        'role',
        'category',
        'name',
        'image',
        'size',
        'color',
        'quantity',
        'discount',
        'description',
        'product_price',
        'shipping_fee',
        'total_price',
        'final_total_price',
        'payment_method',
        'status',
        'reason_cancel',
        'return_reason',
        'return_image1',
        'return_image2',
        'return_image3',
        'return_image4',
        'return_description',
        'return_solution',
        'return_shipping_at',
        'return_accept_at',
        'return_decline_at',
        'return_completed_at',
        'return_failed_at',
        'check_out_at',
        'cancel_at',
        'order_receive_at',
        'mark_as_done_at',
        'ship_at',
        'completed_at',
        'failed_at',
        'return_at',
    ];
}