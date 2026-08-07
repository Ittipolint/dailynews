<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuAccess
{
    public function handle(Request $request, Closure $next, string ...$menus): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Access denied');
        }

        foreach ($menus as $menu) {
            if ($user->canAccessMenu($menu)) {
                return $next($request);
            }
        }

        abort(403, 'ไม่มีสิทธิ์เข้าใช้งานเมนูนี้');
    }
}
