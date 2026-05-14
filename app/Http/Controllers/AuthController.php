<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\Wallet;

class AuthController extends Controller
{
    public function index()
{
    $users = User::with('roles')->get();
    return response()->json([
        'users' => UserResource::collection($users)
    ]);
}
    public function register(RegisterRequest $request)
{
    $data = $request->validated();

    $user = User::create([
        'full_name' => $data['full_name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'password' => Hash::make($data['password']),
    ]);
    
    $user->assignRole('user');

Wallet::create([
    'user_id' => $user->id,
    'balance' => 0,
]);
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'user'  => new UserResource($user),
        'token' => $token,
    ], 200);}

public function login(LoginRequest $request)
{
    $data = $request->validated();

    $user = User::where('email', $data['email'])->first();

    if (!$user || !Hash::check($data['password'], $user->password)) {
        return response()->json([
            'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
        ], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'user'  => new UserResource($user),
        'token' => $token,
    ]);
}

     public function logout(Request $request)
    {
   $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح.',
        ]);
    }

    public function profile(Request $request)
{
    return response()->json([
            'user' => new UserResource($request->user()->load('roles'))
        ]);
}
}