<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Halaman portal role lain (mis. redirect "intended" setelah ganti akun, link lama, bookmark):
        // arahkan ke dashboard milik user, bukan halaman 403 buntu. Akses tetap ditolak.
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, \Illuminate\Http\Request $request) {
            $user = $request->user();

            if (! $user || ! $request->isMethod('GET') || $request->expectsJson()) {
                return null;
            }

            $home = $user->getRedirectRoute();

            // Hindari redirect berulang kalau dashboard-nya sendiri juga tidak boleh diakses (user tanpa role)
            if ($request->is(ltrim($home, '/'))) {
                return null;
            }

            return redirect($home)->with('error', 'Anda tidak punya akses ke halaman tersebut, jadi diarahkan ke dashboard Anda');
        });

        // Halaman error bermerek IAMS (Pages/Error.vue) menggantikan halaman bawaan Laravel.
        // 403/404 selalu; 500/503 hanya saat APP_DEBUG mati supaya detail error tetap terlihat saat development.
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return $response;
            }

            $status = $response->getStatusCode();

            if ($status === 419) {
                return back()->with('error', 'Sesi Anda berakhir. Silakan coba lagi.');
            }

            if (in_array($status, [403, 404], true) || (in_array($status, [500, 503], true) && ! config('app.debug'))) {
                return \Inertia\Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
