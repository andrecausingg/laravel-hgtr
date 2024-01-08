<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInfoModel extends Model
{
    use HasFactory;
    protected $table = 'user_info_tbl';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'contact_num',
        'address_1',
        'address_2',
        'region_code',
        'province_code',
        'city_or_municipality_code',
        'region_name',
        'province_name',
        'city_or_municipality_name',
        'barangay',
        'description_location',
        'created_at',
        'updated_at'
    ];
}
