<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'super.admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'subscribed' => \App\Http\Middleware\EnsureSubscriptionActive::class,
            'bot.key' => \App\Http\Middleware\AuthenticateBotApiKey::class,
        ]);

        // Bot chat is API-key authenticated (not session-based), so CSRF
        // does not apply. Payment confirm is called from the gateway flow.
        $middleware->validateCsrfTokens(except: [
            'api/bot/chat',
        ]);

        // Already-authenticated users visiting /login go to their own
        // dashboard (admin -> /admin, client -> /crm) instead of home.
        // Fixes: logged-in admin opening the client login link and
        // getting bounced to the homepage instead of the login form.
        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            if ($user === null) {
                return route('login');
            }

            if ($user->isSuperAdmin()) {
                return route('admin.dashboard');
            }

            return $user->hasActiveSubscription()
                ? route('crm.dashboard')
                : route('crm.payment-required');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
