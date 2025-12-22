<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class VerifyHmacSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $serviceId = $request->header('x-service-id');
        $timestamp = $request->header('x-service-timestamp');
        $signature = $request->header('x-service-signature');

        if (! $this->hasRequiredHeaders($serviceId, $timestamp, $signature)) {
            return $this->unauthorizedResponse('Missing required authentication headers.');
        }

        if (! $this->isAllowedServiceId((string) $serviceId)) {
            return $this->unauthorizedResponse('Invalid service identifier.');
        }

        if (! $this->isTimestampValid((string) $timestamp)) {
            return $this->unauthorizedResponse('Request timestamp is outside acceptable window.');
        }

        if (! $this->isSignatureValid($request, (string) $serviceId, (string) $timestamp, (string) $signature)) {
            return $this->unauthorizedResponse('Invalid signature.');
        }

        $requestId = $request->header('x-request-id');
        $request->attributes->set('request_id', is_string($requestId) && $requestId !== '' ? $requestId : (string) Str::uuid());

        return $next($request);
    }

    private function hasRequiredHeaders(?string $serviceId, ?string $timestamp, ?string $signature): bool
    {
        return $serviceId !== null
            && $serviceId !== ''
            && $timestamp !== null
            && $timestamp !== ''
            && $signature !== null
            && $signature !== '';
    }

    private function isAllowedServiceId(string $serviceId): bool
    {
        /** @var array<int, string> $allowedServiceIds */
        $allowedServiceIds = Config::get('internal-services.allowed_service_ids', []);
        return in_array($serviceId, $allowedServiceIds, true);
    }

    private function isTimestampValid(string $timestamp): bool
    {
        if (!ctype_digit($timestamp)) {
            return false;
        }
        $tolerance = (int) Config::get('internal-services.timestamp_tolerance', 300);
        $now = time();
        $ts = (int) $timestamp;
        return abs($now - $ts) <= $tolerance;
    }

    private function isSignatureValid(Request $request, string $serviceId, string $timestamp, string $signature): bool
    {
        $secret = (string) Config::get('internal-services.secret');
        $canonical = implode("\n", [
            strtoupper($request->getMethod()),
            $request->getPathInfo(),
            $request->getContent(),
            $serviceId,
            $timestamp,
        ]);
        $expected = hash_hmac('sha256', $canonical, $secret);
        return hash_equals($expected, $signature);
    }

    private function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }
}

