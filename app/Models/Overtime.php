<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'overtimes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at'
    ];

    /**
     * Relation
     *
     */
    public function checkIn()
    {
        return $this->hasMany('App\Models\DetailOvertime', 'overtime_id', 'id')->where('tipe_cek', 'in');
    }

    public function checkOut()
    {
        return $this->hasMany('App\Models\DetailOvertime', 'overtime_id', 'id')->where('tipe_cek', 'out');
    }

    public function logApproval()
    {
        return $this->hasMany('App\Models\LogApprovalOvertime', 'overtime_id', 'id');
    }

    public function detailOvertime()
    {
        return $this->hasMany('App\Models\DetailOvertime', 'overtime_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}
