<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn() => null);
        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/*'
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // 1. أخطاء الإدخال (Validation Exception)
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => ['ar' => 'البيانات المدخلة غير صحيحة', 'en' => 'The given data was invalid'],
                'errors'  => $e->errors(),
            ], 422);
        });

        // 2. أخطاء صلاحيات مكتبة Spatie
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => ['ar' => 'ليس لديك الصلاحية للقيام بهذا الإجراء', 'en' => 'You do not have the required role or permission'],
            ], 403);
        });

        // 3. أخطاء الصلاحيات العامة من لاراڤيل (Authorization Exception)
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => ['ar' => 'غير مصرح لك بتنفيذ هذا الإجراء', 'en' => 'You are not authorized to perform this action'],
            ], 403);
        });

        // 4. عدم وجود العنصر في قاعدة البيانات (Model Not Found - 404)
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => ['ar' => 'العنصر غير موجود', 'en' => 'Item not found'],
            ], 404);
        });

        // 5. عدم تسجيل الدخول (Unauthenticated - 401)
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => ['ar' => 'يجب تسجيل الدخول أولاً', 'en' => 'Unauthenticated'],
            ], 401);
        });
    })->create();
