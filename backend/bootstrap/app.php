<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            'simple.auth' => \App\Http\Middleware\SimpleApiAuth::class,
        ]);

        // 🔥 PAKSA JANGAN REDIRECT
        $middleware->redirectGuestsTo(function (Request $request) {
            // Kalau request API (atau mau JSON), jangan redirect ke route('login')
            if ($request->is('api/*') || $request->expectsJson()) {
                return null; // Ini akan memicu AuthenticationException (JSON)
            }
            // Fallback ke route login yang sudah kita buat di web.php
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        
        // 🔥 CUSTOM ERROR RESPONSE (Biar gak HTML)
        
        // 1. Kalau Token Salah/Kosong (401)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Token Invalid.'
                ], 401);
            }
        });

        // 2. Kalau Route Tidak Ditemukan (404)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Endpoint API tidak ditemukan.'
                ], 404);
            }
        });

    })->create();