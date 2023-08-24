<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    use HasFactory;
    protected $table = 'product_tbl';
    protected $primaryKey = 'id';
    protected $fillable = [ 
        'group_id',
        'role',
        'image',
        'name',
        'price',
        'quantity',
        'category',
        'color',
        'size',
        'discount',
        'description',
        'created_at',
        'updated_at'
    ];
}
