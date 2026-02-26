<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticable;

class User extends Authenticable
{
    use SoftDeletes;

    //Atributes that are hidden for serialization:
    protected $hidden = [
        "password",
        "token"
    ];
}
