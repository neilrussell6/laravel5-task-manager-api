<?php namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiAclUtils;
use Neilrussell6\Laravel5JsonApi\Facades\JsonApiUtils;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AccessTokensController extends Controller
{
    /**
     * AccessTokensController constructor
     *
     * @param $model
     */
    public function __construct ($model = null)
    {
        parent::__construct($model);
    }

    /**
     * create a JWT access token and return a JSON API formatted response
     *
     * @param Request $request
     * @return mixed
     */
    public function create (Request $request)
    {
        $request_data = $request->all();
        $credentials = [
            'password' => $request_data['data']['attributes']['password']
        ];

        if (array_key_exists('username', $request_data['data']['attributes'])) {
            $credentials['username'] = $request_data['data']['attributes']['username'];

        } else if (array_key_exists('email', $request_data['data']['attributes'])) {
            $credentials['email'] = $request_data['data']['attributes']['email'];
        }

        // attempt to verify the credentials and create an access token for the user
        try {
            $token = JWTAuth::attempt($credentials);

            if (!$token) {
                $error_code = 401;
                $error_objects = JsonApiUtils::makeErrorObjects([[
                    'detail' => "Invalid credentials",
                    'title' => "Unauthorized",
                ]], $error_code);
                return Response::make([ 'errors' => $error_objects ], $error_code);
            }
        }

        // could not successfully encode the access token
        catch (JWTException $e) {
            $error_code = 500;
            $error_objects = JsonApiUtils::makeErrorObjects([[
                'detail' => "An error occurred while attempting to create access token",
                'title' => "Server error",
            ]], $error_code);
            
            return Response::make([ 'errors' => $error_objects ], $error_code);
        }

        // return newly created resource
        return Response::make([
            'links' => JsonApiUtils::makeTopLevelLinksObject($request->url()),
            'data' => [
                'type' => 'access_tokens',
                'attributes' => [
                    'access_token' => $token
                ]
            ]
        ], 201);
    }

    public function showOwner (Request $request)
    {
        // is minimal ? (return resource identifier object ie. only type and id)
        $action         = $request->route()->getAction();
        $is_minimal     = array_key_exists('is_minimal', $action) && $action['is_minimal'];

        // get user associated with access token
        $user                       = JWTAuth::parseToken()->toUser();
        $related_data               = !is_null($user) ? $user->toArray() : null;
        $related_model_class_name   = User::class;
        $related_model              = new $related_model_class_name();
        $include_resource_object_links = false;

        // ACL
        if (!is_null(config('jsonapi.acl.check_access')) && config('jsonapi.acl.check_access') !== false) {
            $errors = JsonApiAclUtils::accessCheck($request->route()->getName(), $user, $user);
            if (!empty($errors)) {
                return Response::make([ 'errors' => $errors ], 403);
            }
        }

        return Response::item($request, $related_data, $related_model, 200, $include_resource_object_links, $is_minimal);
    }
}
