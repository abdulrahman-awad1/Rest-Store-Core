<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailVerification;
use App\Resources\UserResource;
use App\Services\AuthService;
use App\trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthUserController extends Controller
{
    use ApiResponse;

    public function __construct(private AuthService $authService) {}

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|between:2,100',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = $this->authService->register($data);

        return $this->returnData('user', new UserResource($user), 'User registered successfully');
    }

    public function verifyEmail(Request $request)
{
    $data = $request->validate([
        'token' => 'required|string'
    ]);

    $record = EmailVerification::where('token',$data['token'])
        ->where('expires_at','>',now())
        ->first();

    if (!$record) {
        return $this->returnError('VER001','Invalid token');
    }

    $user = User::where('email',$record->email)->first();

    $user->update([
        'email_verified_at' => now()
    ]);

    $record->delete();

    return $this->successMessage('Email verified successfully');
}


    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login($data);

        if (!$result) {
            return $this->returnError('E001', 'Invalid credentials');
        }
        if (isset($result['error'])) {
            return $this->returnError('E002', $result['error']);
        }

        return $this->returnData('user', [
            'user'         => new UserResource($result['user']),
            'access_token' => $result['token'],
        ], 'Login successful');
    }

    public function logFacebook(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $result = $this->authService->facebookLogin($request->token);

        return $this->returnData('user', [
            'user'         => new UserResource($result['user']),
            'access_token' => $result['accessToken'],
        ], 'Facebook login successful');
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
            return $this->successMessage('Logout successful');
        }
    
        return $this->returnError('401', 'Unauthenticated', 401);
    }
    

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $data['email'])->first();
        $this->authService->sendReset($user);

        return $this->successMessage('Password reset link sent');
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token'    => 'required|string|size:6',
            'email'    => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = $this->authService->resetPassword($data);

        if (!$user) {
            return $this->returnError('OTP001', 'Invalid or expired token');
        }

        return $this->returnData('user', new UserResource($user), 'Password updated successfully');
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6',
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $updatedUser = $this->authService->changePassword($user, $data);

        return $this->returnData('user', new UserResource($updatedUser), 'Password changed successfully');
    }
}
