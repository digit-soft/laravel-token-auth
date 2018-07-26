<?php

namespace DigitSoft\LaravelTokenAuth\Tests;

use DigitSoft\LaravelTokenAuth\Eloquent\HasTokens;

class User extends \Illuminate\Foundation\Auth\User
{
    use HasTokens;

    protected $fillable = ['id', 'email', 'password'];
}