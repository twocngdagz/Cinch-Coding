<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

final class VerifyHmacSignature
{
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

        $tolerance = Config::integer('internal-services.timestamp_tolerance', 300);

        return abs($currentTime - $requestTime) <= $tolerance;
    }

    private function isSignatureValid(Request $request, string $serviceId, string $timestamp, string $signature): bool
    {
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
