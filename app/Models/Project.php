<?php namespace App\Models;

use App\Traits\Ownable;
use Illuminate\Database\Eloquent\Model;
use Neilrussell6\Laravel5JsonApi\Traits\Validatable;

class Project extends Model
{
    use Validatable, Ownable;
    
    const STATUS_INCOMPLETE = 1;
    const STATUS_COMPLETE   = 2;
    const STATUS_TRASH      = 3;

    protected $fillable     = ['user_id', 'name', 'status'];
    protected $hidden       = [];
    protected $casts        = [
        'status' => 'integer',
    ];

    public $type = 'projects';
    public $rules = [
        'name' => 'required'
    ];
    public $available_includes = ['owner', 'tasks'];
    public $default_includes = ['owner'];

    // ----------------------------------------------------
    // eloquent events
    // ----------------------------------------------------

    protected static function boot() {
        parent::boot();

        static::deleting(function($project) {
            $project->tasks->each(function($task) {
                $task->delete();
            });
        });
    }

    // ----------------------------------------------------
    // relationships
    // ----------------------------------------------------

    public function owner ()
    {
        return $this->belongsTo('App\Models\User', 'user_id'); // we would not need to provide a foreign key if the method was called 'user'
    }

    public function tasks ()
    {
        return $this->hasMany('App\Models\Task');
    }
}
