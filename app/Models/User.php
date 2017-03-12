<?php namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laratrust\Contracts\Ownable;
use Neilrussell6\Laravel5JsonApi\Traits\Validatable;
use Laratrust\Traits\LaratrustUserTrait;

class User extends Authenticatable implements Ownable
{
    use Notifiable, Validatable, LaratrustUserTrait;

    protected $fillable     = ['username', 'first_name', 'last_name', 'email', 'password'];
    protected $hidden       = ['password', 'remember_token'];

    public $type = 'users';
    public $rules = [
        'username' => 'required',
        'first_name' => 'required',
        'last_name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!$#%]).*$/|confirmed',
    ];
    public $available_includes = ['projects', 'tasks'];
    public $default_includes = ['projects'];
    public $owner_key = 'id';

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function ownerKey()
    {
        return $this->id;
    }

    public function projects ()
    {
        return $this->hasMany('App\Models\Project');
    }

    public function tasks ()
    {
        return $this->hasMany('App\Models\Task');
    }
}
