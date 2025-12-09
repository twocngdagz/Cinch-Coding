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
        if (! is_numeric($timestamp)) {
            return false;
        }

        $requestTime = (int) $timestamp;
        $currentTime = time();

        /** @var int $tolerance */
        $tolerance = Config::integer('internal-services.timestamp_tolerance', 300);

        return abs($currentTime - $requestTime) <= $tolerance;
    }

    private function isSignatureValid(Request $request, string $serviceId, string $timestamp, string $signature): bool
    {
        /** @var string|null $secret */
        $secret = Config::get('internal-services.secret');

        if ($secret === null || $secret === '') {
            return false;
        }

        $expectedSignature = $this->computeSignature(
            $request->method(),
            $request->path(),
            $request->getContent(),
            $serviceId,
            $timestamp,
            $secret
        );

        return hash_equals($expectedSignature, $signature);
    }

    private function computeSignature(
        string $method,
        string $path,
        string $body,
        string $serviceId,
        string $timestamp,
        string $secret
    ): string {
        $payload = implode("\n", [
            strtoupper($method),
            '/'.ltrim($path, '/'),
            $body,
            $serviceId,
            $timestamp,
        ]);

        return hash_hmac('sha256', $payload, $secret);
    }

    private function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => $message,
        ], Response::HTTP_UNAUTHORIZED);
    }
}
