<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

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

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = []): mixed
    {
        $url = $this->buildUrl($path, $query);

        $response = $this->createRequest('GET', $path, '')
            ->get($url);

        return $this->handleResponse($response);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function post(string $path, array $data = []): mixed
    {
        $url = $this->buildUrl($path);
        $body = $data !== [] ? json_encode($data, JSON_THROW_ON_ERROR) : '';

        $response = $this->createRequest('POST', $path, $body)
            ->withBody($body, 'application/json')
            ->post($url);

        return $this->handleResponse($response);
    }

    /**
     * @param  array<string, mixed>  $query
     */
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
}
