<?php namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
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
            'email' => $request_data['data']['attributes']['email'],
            'password' => $request_data['data']['attributes']['password'],
        ];

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
}
