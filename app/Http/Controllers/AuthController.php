<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;


class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validate();

         $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
        ]);
            $user->assignRole('user');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
           'user' => $user->makeHidden(['password']),
            'token' => $token,
        ]);
}

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
            'user' => $user->makeHidden(['password']),
            'token' => $token,
        ]);
    }

     public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح.',
        ]);
    }

    public function profile(Request $request)
{
    return response()->json([
        'user' => $request->user()->load('roles')
    ]);
}
}