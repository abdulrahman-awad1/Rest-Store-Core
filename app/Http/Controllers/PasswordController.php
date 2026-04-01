<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Resources\UserResource;
use App\Services\AuthService;
use App\trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    use ApiResponse;

    public function __construct(private AuthService $authService) {}

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
            'token' => 'required|digits:6',
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
