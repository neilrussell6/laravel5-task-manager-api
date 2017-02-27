<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    const ROLE_ADMINISTRATOR = 'administrator';
    const ROLE_DEMO = 'demo';
    const ROLE_SUBSCRIBER = 'subscriber';
}
