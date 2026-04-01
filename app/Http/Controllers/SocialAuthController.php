<?php

namespace App\Http\Controllers;

use App\Resources\UserResource;
use App\Services\AuthService;
use App\trait\ApiResponse;
use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    use ApiResponse;

    public function __construct(private AuthService $authService) {}

    public function logFacebook(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $result = $this->authService->facebookLogin($request->token);

        return $this->returnData('user', [
            'user'         => new UserResource($result['user']),
            'access_token' => $result['accessToken'],
        ], 'Facebook login successful');
    }
}
