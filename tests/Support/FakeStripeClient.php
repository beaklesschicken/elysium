<?php

namespace Tests\Support;

use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

/**
 * Stands in for Stripe's HTTP client so Cashier calls never leave the test.
 * Responses are matched on "METHOD /v1/path" prefixes; every request is recorded.
 */
class FakeStripeClient implements ClientInterface
{
    /** @var list<array{method: string, path: string, params: array<string, mixed>}> */
    public array $requests = [];

    /**
     * @param  array<string, array<string, mixed>>  $responses
     */
    public function __construct(public array $responses = []) {}

    /**
     * @param  array<string, array<string, mixed>>  $responses
     */
    public static function install(array $responses = []): self
    {
        config(['cashier.key' => 'pk_test_fake', 'cashier.secret' => 'sk_test_fake']);

        $client = new self($responses);
        ApiRequestor::setHttpClient($client);

        return $client;
    }

    public static function uninstall(): void
    {
        ApiRequestor::setHttpClient(null);
    }

    /**
     * @return array{0: string, 1: int, 2: array<string, string>}
     */
    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
    {
        $method = strtoupper($method);
        $path = parse_url($absUrl, PHP_URL_PATH);
        $this->requests[] = ['method' => $method, 'path' => $path, 'params' => $params];

        foreach ($this->responses as $route => $body) {
            if (str_starts_with("{$method} {$path}", $route)) {
                return [json_encode($body), 200, ['request-id' => 'req_fake']];
            }
        }

        return [json_encode(['error' => ['message' => "No fake response for {$method} {$path}", 'type' => 'invalid_request_error']]), 404, []];
    }

    /**
     * @return array{method: string, path: string, params: array<string, mixed>}|null
     */
    public function lastRequestTo(string $method, string $path): ?array
    {
        $matches = array_filter($this->requests, fn (array $request): bool => $request['method'] === $method && str_starts_with($request['path'], $path));

        return $matches === [] ? null : end($matches);
    }
}
