<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = [
        'name',
        'trial',
        'groups',
        'temp'
    ];


}