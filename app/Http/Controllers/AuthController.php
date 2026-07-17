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
use Illuminate\Support\Facades\DB;

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

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'],
                'password'  => Hash::make($data['password']),
            ]);

            $user->assignRole('user');

            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);

            return $user;
        });

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ], 200);
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
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور غير صحيحة.',
            ], 422);
        }

        if ($user->hotels()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف الحساب لوجود فنادق مرتبطة به. يرجى حذف الفنادق أو نقل ملكيتها إلى مدير آخر أولاً.',
            ], 422);
        }

        if ($user->bookings()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف الحساب لوجود حجوزات مرتبطة به.',
            ], 422);
        }

        if ($user->wallet && $user->wallet->balance > 0) {
            return response()->json([
                'message' => 'لا يمكن حذف الحساب لوجود رصيد في المحفظة. يرجى التواصل مع الإدارة لتسوية الرصيد أولاً.',
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'تم حذف الحساب بنجاح.',
        ]);
    }
}
