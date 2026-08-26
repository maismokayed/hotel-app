<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    use ApiResponse;
    public function updateRole(UpdateUserRoleRequest $request, User $user)
    {
        // 1. لا تسمح للـ admin بتغيير دوره هو نفسه
        if ($request->user()->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'لا يمكنك تغيير دورك الخاص.',
                    'en' => 'You cannot change your own role.',
                ],
            ], 422);
        }

        // 2. لا تسمح بتنزيل آخر admin في النظام
        if ($request->role !== 'admin' && $user->hasRole('admin')) {
            $adminCount = User::role('admin')->count();

            if ($adminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => [
                        'ar' => 'لا يمكن تغيير دور آخر مسؤول (admin) في النظام.',
                        'en' => 'You cannot remove the role of the last admin in the system.',
                    ],
                ], 422);
            }
        }

        // 3. لا تسمح بتغيير manager لديه فنادق مرتبطة
        if ($request->role !== 'manager' && $user->hasRole('manager') && $user->hotels()->exists()) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'لا يمكن تغيير دور هذا المستخدم لأن لديه فنادق مرتبطة به. يرجى نقل ملكية الفنادق أولاً.',
                    'en' => 'Cannot change this user role because they have associated hotels. Please transfer hotel ownership first.',
                ],
            ], 422);
        }

        // 4. نفس الدور موجود مسبقاً
        if ($user->hasRole($request->role)) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'المستخدم لديه هذا الدور مسبقاً.',
                    'en' => 'The user already has this role.',
                ],
            ], 422);
        }

        $user->syncRoles([$request->role]);

        return $this->success(
            new UserResource($user->fresh()),
            ['ar' => 'تم تحديث دور المستخدم بنجاح.', 'en' => 'User role updated successfully.']
        );
    }
}
