<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Absensi extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'absensi';

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
        'updated_at'
    ];

    /**
     * Relation
     *
     */
    public function checkIn()
    {
        return $this->hasMany('App\Models\DetailAbsensi', 'absensi_id', 'id')->where('tipe_cek', 'in');
    }

    public function checkOut()
    {
        return $this->hasMany('App\Models\DetailAbsensi', 'absensi_id', 'id')->where('tipe_cek', 'out');
    }

    public function detailAbsensi()
    {
        return $this->hasMany('App\Models\DetailAbsensi', 'absensi_id', 'id');
    }

    public function day()
    {
        return Carbon::now()->startOfWeek()->format('D');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}
