<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogsModel extends Model
{
    use HasFactory;
    protected $table = 'logs_tbl';
    protected $primaryKey = 'id';
    protected $fillable = [ 
        'user_id',
        'ip_address',
        'user_action',
        'details',
        'created_at'
    ];

     // Define the relationship to the user table
     public function user()
     {
         return $this->belongsTo(AuthModel::class, 'user_id')->select('id', 'email', 'role');
     }
}
