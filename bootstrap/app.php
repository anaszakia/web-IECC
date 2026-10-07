<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        $middleware->alias([
            'auth.custom' => \App\Http\Middleware\AuthMiddleware::class,
            'role'        => \App\Http\Middleware\RoleMiddleware::class,
            'permission'  => \App\Http\Middleware\PermissionMiddleware::class,
            'auto.logout' => \App\Http\Middleware\AutoLogoutMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            $status = $e->getStatusCode();
            $views = [
                403 => 'errors.403',
                404 => 'errors.404',
                // 500 => 'errors.500',
            ];
            if (isset($views[$status]) && view()->exists($views[$status])) {
                return response()->view($views[$status], ['exception' => $e], $status);
            }
        });

    })->create();