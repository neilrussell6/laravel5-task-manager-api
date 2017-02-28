<?php namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;

class ProjectsController extends Controller
{
    public $rules = [
        'name' => 'required',
        'status' => 'required'
    ];

    /**
     * ProjectsController constructor
     *
     * @param Project $model
     */
    public function __construct (Project $model)
    {
        parent::__construct($model);
    }
}
