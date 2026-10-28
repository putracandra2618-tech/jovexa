<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request): string => route('login'));
        $middleware->redirectUsersTo(fn (Request $request): string => route($request->user()->role->homeRoute()));

        // The gateway posts this callback server-to-server without a session or
        // CSRF token, so it must be exempt. The callback signature is the
        // authentication mechanism for this endpoint.
        $middleware->validateCsrfTokens(except: [
            'notifikasi/tripay',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $status = $exception->getStatusCode();

            $view = match ($status) {
                Response::HTTP_FORBIDDEN => 'errors.403',
                Response::HTTP_NOT_FOUND => 'errors.404',
                default => null,
            };

            if ($view === null) {
                return null;
            }

            return response()->view($view, ['errors' => new ViewErrorBag], $status);
        });
    })->create();
