<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $table = 'activity';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Relations
     *
     */
    public function images()
    {
        return $this->hasMany('App\Models\Image', 'activity_id', 'id');
    }

    public function progressDetail()
    {
        return $this->hasMany('App\Models\Progress', 'activity_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}
