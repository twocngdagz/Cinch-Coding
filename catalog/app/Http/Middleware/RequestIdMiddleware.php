<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class RequestIdMiddleware
{
    public const HEADER_NAME = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header(self::HEADER_NAME) ?? Str::uuid()->toString();

        $request->attributes->set('request_id', $requestId);

        Log::shareContext([
            'request_id' => $requestId,
        ]);

        $response = $next($request);

        $response->headers->set(self::HEADER_NAME, $requestId);

        return $response;
    }
}
