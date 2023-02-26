<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'nik',
        'email',
        'posisi',
        'direktorat',
        'witel_id',
        'mitra_id',
        'phone',
        'regional_id',
        'is_active',
        'role_id',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relations
     *
     */
    public function absensi()
    {
        return $this->hasMany('App\Models\Absensi', 'user_id', 'id');
    }

    public function absenToday()
    {
        return $this->hasOne('App\Models\Absensi', 'user_id', 'id')->where('created_at', 'LIKE', '%' . date('Y-m-d') . '%');
    }

    public function notPresent()
    {
        return $this->hasMany('App\Models\NotPresent', 'user_id', 'id');
    }

    public function activity()
    {
        return $this->hasMany('App\Models\Activity', 'user_id', 'id');
    }

    public function regional()
    {
        return $this->belongsTo('App\Models\Regional', 'regional_id', 'id');
    }

    public function witel()
    {
        return $this->belongsTo('App\Models\Witel', 'witel_id', 'id');
    }

    public function mitra()
    {
        return $this->belongsTo('App\Models\Mitra', 'mitra_id', 'id');
    }
}
