<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check()) {
            if ($request->isAjax()) {
                return Response::json(['message' => 'Unauthenticated.'], 401);
            }

            Session::flash('error', 'Please log in to continue.');

            return Response::redirect('/login');
        }

        return $next($request);
    }
}
