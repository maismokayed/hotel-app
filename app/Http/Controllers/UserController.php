<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\User;

class UserController extends Controller
{
    public function updateRole(UpdateUserRoleRequest $request, User $user)
    {
        // 1. ما تسمحيش للـ admin يغيّر دوره هو نفسه
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'لا يمكنك تغيير دورك الخاص.',
            ], 422);
        }

        // 2. ما تسمحيش تنزيل آخر admin بالنظام
        if ($request->role !== 'admin' && $user->hasRole('admin')) {
            $adminCount = User::role('admin')->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'message' => 'لا يمكن تنزيل دور آخر مسؤول (admin) في النظام.',
                ], 422);
            }
        }

        // 3. ما تسمحيش تنزيل manager عندو فنادق مرتبطة فيه
        if ($request->role !== 'manager' && $user->hasRole('manager') && $user->hotels()->exists()) {
            return response()->json([
                'message' => 'لا يمكن تغيير دور هذا المستخدم لأن لديه فنادق مرتبطة به. يرجى نقل ملكية الفنادق أولاً.',
            ], 422);
        }

        // 4. ما تنفذيش عملية فاضية لو نفس الدور أصلاً
        if ($user->hasRole($request->role)) {
            return response()->json([
                'message' => 'المستخدم لديه هذا الدور مسبقاً.',
            ], 422);
        }

        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => 'تم تحديث دور المستخدم بنجاح.',
            'role' => $request->role,
        ]);
    }
}
