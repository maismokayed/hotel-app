<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;



class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|unique:users',
            'password' => ['required', 'string', Password::defaults()],
        ]);

         $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
           'user' => $user->makeHidden(['password']),
            'token' => $token,
        ]);
}

public function login(Request $request)
    {
            $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
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
}