<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Restrict the request to users holding one of the given roles.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, UserRole|string ...$roles): Response
    {
        $user = $request->user();

        $allowed = array_map(
            static fn (UserRole|string $role): UserRole => is_string($role) ? UserRole::from($role) : $role,
            $roles,
        );

        if ($user === null || ! in_array($user->role, $allowed, strict: true)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
