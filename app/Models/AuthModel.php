<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthModel extends Model
{
    use HasFactory;
    protected $table = 'users_tbl';
    protected $primaryKey = 'id';
    protected $fillable = [
        'email',
        'password',
        'role',
        'status',
        'ip_address',
        'verification_num',
        'verification_key',
        'session_verify_email',
        'session_pass_reset',
        'session_login',
        'verified_at',
        'update_pass_reset_at',
        'created_at'
    ];
}
