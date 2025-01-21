<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminVerify extends Model
{
    public $table = "admin_verify";
    use HasFactory;
    protected $fillable = [
        'admin_id',
        'token',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
