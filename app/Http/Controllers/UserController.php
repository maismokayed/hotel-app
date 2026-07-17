<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\User;

class UserController extends Controller
{
    public function updateRole(UpdateUserRoleRequest $request, User $user)
    {
        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => 'User role updated successfully.',
            'role' => $request->role,
        ]);
    }
}
