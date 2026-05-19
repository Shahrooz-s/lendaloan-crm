<?php

namespace Modules\Sms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Facades\Innoclapps;
use Symfony\Component\HttpFoundation\Response;

class ModuleActivationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (settings()->get('run_optimize_command') === true)
        {
            Innoclapps::optimize();
            settings()->set(['run_optimize_command' => false])->save();

        }
    }
}
