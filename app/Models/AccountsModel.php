<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountsModel extends Model
{
    use HasFactory;
    protected $table = 'users_tbl';
    protected $primaryKey = 'id';
    protected $fillable = [
        'email',
        'role',
        'password',
        'status',
        'ip_address',
        'verified_at',
        'created_at'
    ];
}
