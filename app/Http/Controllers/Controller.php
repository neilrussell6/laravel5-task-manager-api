<?php namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Neilrussell6\Laravel5JsonApi\Http\Controllers\JsonApiController;

class Controller extends JsonApiController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    const PAGINATION_LIMIT = 100;

    protected $model = null;

    public $messages = [
        'required' => 'The :attribute field is required.',
        'email' => 'A valid email is required for :attribute field.',
        'unique' => ':attribute field must be unique.',
        'min' => ':attribute field must be at least :min character in length.',
    ];

    /**
     * Controller constructor.
     * @param $model
     */
    public function __construct ($model = null)
    {
        if (!is_null($model)) {
            $this->model = new $model();
        }
    }
}
