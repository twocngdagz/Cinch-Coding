<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\RequestContext;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class InternalHttpClient
{
    private string $baseUrl;

    private string $secret;

    private string $serviceId;

    public function __construct(string $baseUrl, string $secret, string $serviceId)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->secret = $secret;
        $this->serviceId = $serviceId;
    }

    public function get(string $path, array $query = []): mixed
    {
        $url = $this->buildUrl($path, $query);

        $response = $this->createRequest('GET', $path, '')
            ->get($url);

        return $this->handleResponse($response);
    }

    public function post(string $path, array $data = []): mixed
    {
        $url = $this->buildUrl($path);
        $body = $data !== [] ? json_encode($data, JSON_THROW_ON_ERROR) : '';
        $requestId = RequestContext::getRequestId();

        $this->logOutgoingRequest($path, $data, $requestId);

        $response = $this->createRequest('POST', $path, $body)
            ->withBody($body, 'application/json')
            ->post($url);

        $result = $this->handleResponse($response);

        $this->logSuccessResponse($path, $result, $requestId);

        return $result;
    }

    public function sendEmailNotification(string $path, int $orderId): void
    {
        $url = $this->buildUrl($path);
        $data = ['order_id' => $orderId];
        $body = json_encode($data, JSON_THROW_ON_ERROR);
        $requestId = RequestContext::getRequestId();

        Log::channel('internal')->info('', [
            'event' => 'email_notification_request',
            'request_id' => $requestId,
            'service' => 'checkout',
            'endpoint' => $path,
            'extra' => [
                'order_id' => $orderId,
            ],
        ]);

        $response = $this->createRequest('POST', $path, $body)
            ->withBody($body, 'application/json')
            ->post($url);

        $response->throw();

        if ($response->status() === 202) {
            Log::channel('internal')->info('', [
                'event' => 'email_notification_acknowledged',
                'request_id' => $requestId,
                'service' => 'checkout',
                'endpoint' => $path,
                'extra' => [
                    'order_id' => $orderId,
                ],
            ]);
        }
    }

    private function buildUrl(string $path, array $query = []): string
    {
        $url = $this->baseUrl.'/'.ltrim($path, '/');

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }

    private function createRequest(string $method, string $path, string $body): PendingRequest
    {
        $timestamp = (string) time();
        $signature = $this->computeSignature($method, $path, $body, $timestamp);

        $headers = [
            'x-service-id' => $this->serviceId,
            'x-service-timestamp' => $timestamp,
            'x-service-signature' => $signature,
        ];

        $requestId = $this->getRequestId();
        if ($requestId !== null) {
            $headers['x-request-id'] = $requestId;
        }

        return Http::withHeaders($headers)->acceptJson();
    }

    private function computeSignature(string $method, string $path, string $body, string $timestamp): string
    {
        $payload = implode("\n", [
            strtoupper($method),
            '/'.ltrim($path, '/'),
            $body,
            $this->serviceId,
            $timestamp,
        ]);

        return hash_hmac('sha256', $payload, $this->secret);
    }

    private function getRequestId(): ?string
    {
        $requestId = request()->attributes->get('request_id');

        return is_string($requestId) ? $requestId : null;
    }

    private function handleResponse(Response $response): mixed
    {
        $response->throw();

        return $response->json();
    }

    private function logOutgoingRequest(string $path, array $data, string $requestId): void
    {
        if (str_contains($path, '/internal/products/validate')) {
            Log::channel('internal')->info('', [
                'event' => 'catalog_validation_call',
                'request_id' => $requestId,
                'service' => 'checkout',
                'endpoint' => $path,
                'extra' => [
                    'items_count' => count($data['items'] ?? []),
                ],
            ]);
        }
    }

    private function logSuccessResponse(string $path, mixed $result, string $requestId): void
    {
        if (str_contains($path, '/internal/products/validate') && is_array($result)) {
            Log::channel('internal')->info('', [
                'event' => 'catalog_validation_success',
                'request_id' => $requestId,
                'service' => 'checkout',
                'endpoint' => $path,
                'extra' => [
                    'validated_items' => count($result['items'] ?? []),
                ],
            ]);
        }
    }
}
