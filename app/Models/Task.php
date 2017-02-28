<?php namespace App\Models;

use App\Traits\Ownable;
use Illuminate\Database\Eloquent\Model;
use Neilrussell6\Laravel5JsonApi\Traits\Validatable;

class Task extends Model
{
    use Validatable, Ownable;
    
    const STATUS_INCOMPLETE = 1;
    const STATUS_COMPLETE   = 2;
    const STATUS_TRASH      = 3;

    protected $fillable     = ['project_id', 'user_id', 'name', 'status'];
    protected $hidden       = [];
    protected $casts        = [
        'status' => 'integer',
    ];

    public $type = 'tasks';
    public $rules = [
        'name' => 'required'
    ];
    public $available_includes = ['owner', 'project'];
    public $default_includes = ['owner', 'project'];

    public function owner ()
    {
        return $this->belongsTo('App\Models\User', 'user_id'); // we would not need to provide a foreign key if the method was called 'user'
    }

    public function project ()
    {
        return $this->belongsTo('App\Models\Project', 'project_id');
    }
}
