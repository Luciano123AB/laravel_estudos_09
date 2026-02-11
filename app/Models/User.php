<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    //Atributes that are hidden for serialization:
    protected $hidden = [
        "password",
        "token"
    ];
}
