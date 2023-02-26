<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogApprovalOvertime extends Model
{
    use HasFactory;

    protected $table = 'log_approval_overtime';

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
    public function user()
    {
        return $this->belongsTo('App\Models\Master\User', 'username', 'username');
    }
}
