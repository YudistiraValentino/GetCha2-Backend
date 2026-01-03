<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException; // 👈 Import ini

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        $middleware->alias([
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
        ]);

        // 🔥 OPSI 1: Mencegah Redirect untuk Guest
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return null; // Kalau null, Laravel akan lempar AuthenticationException
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 🔥 OPSI 2: Menangkap Error Auth dan paksa jadi JSON
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated (Token Invalid or Expired)',
                ], 401);
            }
        });
    })->create();