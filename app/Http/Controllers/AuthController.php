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
use App\Traits\ApiResponse;

class AuthController extends Controller
{
    use ApiResponse;
    public function index()
    {
        $users = User::with('roles')->get();

        return $this->success(
            UserResource::collection($users),
            ['ar' => 'تم جلب المستخدمين بنجاح', 'en' => 'Users fetched successfully']
        );
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
            'success' => true,
            'message' => [
                'ar' => 'تم إنشاء الحساب بنجاح',
                'en' => 'Account created successfully',
            ],
            'data' => [
                'user'  => new UserResource($user),
                'token' => $token,
            ]
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
                    'en' => 'Invalid email or password.',
                ],
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم تسجيل الدخول بنجاح',
                'en' => 'Logged in successfully',
            ],
            'data' => [
                'user'  => new UserResource($user),
                'token' => $token,
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم تسجيل الخروج بنجاح.',
                'en' => 'Logged out successfully.',
            ],
        ], 200);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم جلب البيانات الشخصية بنجاح',
                'en' => 'Profile fetched successfully',
            ],
            'data' => [
                'user' => new UserResource($request->user()->load('roles'))
            ]
        ], 200);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'كلمة المرور غير صحيحة.',
                    'en' => 'Incorrect password.',
                ],
            ], 422);
        }

        if ($user->hotels()->exists()) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'لا يمكن حذف الحساب لوجود فنادق مرتبطة به. يرجى حذف الفنادق أو نقل ملكيتها إلى مدير آخر أولاً.',
                    'en' => 'Cannot delete account because there are associated hotels. Please delete or transfer ownership first.',
                ],
            ], 422);
        }

        if ($user->bookings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'لا يمكن حذف الحساب لوجود حجوزات مرتبطة به.',
                    'en' => 'Cannot delete account because there are active bookings associated with it.',
                ],
            ], 422);
        }

        if ($user->wallet && $user->wallet->balance > 0) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'لا يمكن حذف الحساب لوجود رصيد في المحفظة. يرجى التواصل مع الإدارة لتسوية الرصيد أولاً.',
                    'en' => 'Cannot delete account because there is a remaining wallet balance. Please contact support.',
                ],
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم حذف الحساب بنجاح.',
                'en' => 'Account deleted successfully.',
            ],
        ], 200);
    }
}
