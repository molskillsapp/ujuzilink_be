<?php
/*
 * File name: Authenticate.php
 * Last modified: 2024.07.23 at 21:00:42
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @param string ...$guards
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards): mixed
    {
        try {
            $this->authenticate($request, $guards);
            return $next($request);
        } catch (AuthenticationException $e) {
            if ($request->ajax()) {
                return response()->json(['error' => "Not authenticated"], 401);
            } else {
                throw new AuthenticationException(
                    'Unauthenticated.', $guards, $this->redirectTo($request)
                );
            }
        }
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param Request $request
     * @return string
     */
    protected function redirectTo(Request $request): string
    {
        return route('login');
    }
}
