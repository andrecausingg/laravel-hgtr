<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VouchersModel extends Model
{
    use HasFactory;
    protected $table = 'vouchers_tbl';
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        'name',
        'status',
        'discount',
        'activate_at',
        'used_at',
        'start_at',
        'expire_at',
    ];
}
