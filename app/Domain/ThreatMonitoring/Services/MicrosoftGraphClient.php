<?php

namespace App\Domain\ThreatMonitoring\Services;

use App\Models\Integration;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\LazyCollection;
use RuntimeException;
use Throwable;

class MicrosoftGraphClient
{
    private const GRAPH_BASE_URL = 'https://graph.microsoft.com/v1.0';
    private const TOKEN_URL_TEMPLATE = 'https://login.microsoftonline.com/%s/oauth2/v2.0/token';
    private const MAX_ATTEMPTS = 4;

    /**
     * @var array<int, array{token: string, expires_at: int}>
     */
    private array $tokenCache = [];

    public function __construct(private readonly Factory $http)
    {
    }

    public function stream(Integration $integration, string $resource, array $query = []): LazyCollection
    {
        return LazyCollection::make(function () use ($integration, $resource, $query) {
            $nextUrl = self::GRAPH_BASE_URL . '/' . ltrim($resource, '/');
            $requestQuery = $query;

            do {
                $response = $this->sendGet($integration, $resource, $nextUrl, $requestQuery);

                foreach ($response->json('value', []) as $item) {
                    yield $item;
                }

                $nextUrl = $response->json('@odata.nextLink');
                $requestQuery = [];
            } while ($nextUrl);
        });
    }

    public function fetch(Integration $integration, string $resource, array $query = []): array
    {
        $response = $this->sendGet(
            $integration,
            $resource,
            self::GRAPH_BASE_URL . '/' . ltrim($resource, '/'),
            $query
        );

        return $response->json('value', []);
    }

    private function request(Integration $integration): PendingRequest
    {
        return $this->http
            ->acceptJson()
            ->withToken($this->accessToken($integration))
            ->timeout(30);
    }

    private function accessToken(Integration $integration): string
    {
        $cacheKey = (int) $integration->getKey();
        $cachedToken = $this->tokenCache[$cacheKey] ?? null;

        if ($cachedToken && $cachedToken['expires_at'] > now()->timestamp) {
            return $cachedToken['token'];
        }

        $credentials = $integration->safeCredentials() ?? [];
        $tenantId = $credentials['tenant_id'] ?? null;
        $clientId = $credentials['client_id'] ?? null;
        $clientSecret = $credentials['client_secret'] ?? null;

        if (!$tenantId || !$clientId || !$clientSecret) {
            throw new RuntimeException(
                'Microsoft Graph credentials are missing or can no longer be decrypted for integration ' . $integration->id . '. Re-enter the integration credentials.'
            );
        }

        $tokenResponse = $this->http
            ->asForm()
            ->timeout(15)
            ->post(sprintf(self::TOKEN_URL_TEMPLATE, $tenantId), [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ])
            ->throw();

        $accessToken = $tokenResponse->json('access_token');

        if (!is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Microsoft Graph token response did not include an access token.');
        }

        $expiresIn = max(60, (int) $tokenResponse->json('expires_in', 3600) - 120);

        $this->tokenCache[$cacheKey] = [
            'token' => $accessToken,
            'expires_at' => now()->addSeconds($expiresIn)->timestamp,
        ];

        return $accessToken;
    }

    private function sendGet(Integration $integration, string $resource, string $url, array $query): Response
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                return $this->request($integration)
                    ->get($url, $query)
                    ->throw();
            } catch (ConnectionException|RequestException $exception) {
                $lastException = $exception;

                if (!$this->shouldRetry($exception) || $attempt === self::MAX_ATTEMPTS) {
                    break;
                }

                usleep($this->retryDelayMicros($attempt, $exception));
            }
        }

        throw new RuntimeException(
            $this->buildErrorMessage($resource, $lastException),
            previous: $lastException instanceof Throwable ? $lastException : null
        );
    }

    private function shouldRetry(ConnectionException|RequestException $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        $response = $exception->response;
        $status = $response->status();
        $message = strtolower((string) $response->json('error.message', ''));

        return $status === 429
            || $status >= 500
            || str_contains($message, 'please try again after some time');
    }

    private function retryDelayMicros(int $attempt, ConnectionException|RequestException $exception): int
    {
        if ($exception instanceof RequestException) {
            $response = $exception->response;
            $retryAfterMs = $response->header('x-ms-retry-after-ms');

            if (is_string($retryAfterMs) && is_numeric($retryAfterMs)) {
                return max(1000, (int) $retryAfterMs) * 1000;
            }

            $retryAfter = $response->header('Retry-After');

            if (is_string($retryAfter) && is_numeric($retryAfter)) {
                return max(1, (int) $retryAfter) * 1000 * 1000;
            }

            if (is_string($retryAfter) && $retryAfter !== '') {
                $retryAt = Carbon::parse($retryAfter, 'UTC');
                $secondsUntilRetry = now()->diffInSeconds($retryAt, false);

                if ($secondsUntilRetry > 0) {
                    return $secondsUntilRetry * 1000 * 1000;
                }
            }
        }

        $delaysMs = [
            1 => 3000,
            2 => 9000,
            3 => 20000,
        ];

        return ($delaysMs[$attempt] ?? 30000) * 1000;
    }

    private function buildErrorMessage(string $resource, ConnectionException|RequestException|null $exception): string
    {
        if ($exception instanceof RequestException) {
            $response = $exception->response;
            $message = (string) $response->json('error.message', $exception->getMessage());

            if (str_contains(strtolower($message), 'please try again after some time')) {
                return sprintf(
                    'Microsoft Graph temporarily rejected the request for %s. The provider asked to retry later.',
                    $resource
                );
            }

            return sprintf(
                'Microsoft Graph request failed for %s with status %d: %s',
                $resource,
                $response->status(),
                $message
            );
        }

        if ($exception instanceof ConnectionException) {
            return sprintf(
                'Microsoft Graph connection failed for %s: %s',
                $resource,
                $exception->getMessage()
            );
        }

        return sprintf('Microsoft Graph request failed for %s.', $resource);
    }
}
