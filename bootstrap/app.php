<?php

use App\Exceptions\SchedulingConflict;
use App\Exceptions\StaleRecord;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [AssignRequestId::class], append: [SecurityHeaders::class]);
        // Reject requests whose Host header is not APP_URL's host (or a subdomain). Inactive in local/testing.
        $middleware->trustHosts();
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation']);

        // Expected business outcomes in admin workflows: return to the form with an explanation.
        $exceptions->render(fn (StaleRecord|SchedulingConflict $e, Request $request) => $request->expectsJson()
            ? response()->json(['message' => $e->getMessage()], 409)
            : back()->withInput()->with('error', $e->getMessage()));
    })->create();
