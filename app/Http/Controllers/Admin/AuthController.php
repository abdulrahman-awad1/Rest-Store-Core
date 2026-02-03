<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\trait\apiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\AdminResource;


class AuthController extends Controller
{
    use apiResponse;

    

    public function register_admin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:admins',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $admin = Admin::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));

        return $this->returnData('admin', new AdminResource($admin), 'successfully registered');
    }

    public function login_admin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return $this->returnError('E001', 'بيانات الدخول غير صحيحة');
        }

        $token = $admin->createToken('auth_token')->plainTextToken;

        return $this->returnData('admin', ['admin' => new AdminResource($admin),'access_token' => $token], 'successfully');
        
    }

    public function logout_admin(Request $request)
    {
        $admin = $request->user(); // current authenticated admin
        $admin->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successfully']);
    }
}
