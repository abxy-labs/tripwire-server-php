<?php

declare(strict_types=1);

namespace Tripwire\Server;

use GuzzleHttp\Client as GuzzleClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as PsrHttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tripwire\Server\Api\FingerprintsApi;
use Tripwire\Server\Api\GateApi;
use Tripwire\Server\Api\SessionsApi;
use Tripwire\Server\Api\TeamsApi;
use Tripwire\Server\Api\WebhooksApi;
use Tripwire\Server\Exception\TripwireConfigurationError;
use Tripwire\Server\Http\HttpClient;

final class Client
{
    private const DEFAULT_BASE_URL = 'https://api.tripwirejs.com';

    private SessionsApi $sessions;
    private FingerprintsApi $fingerprints;
    private TeamsApi $teams;
    private GateApi $gate;
    private WebhooksApi $webhooks;

    /**
     * @throws TripwireConfigurationError
     */
    public function __construct(
        ?string $secretKey = null,
        ?string $baseUrl = null,
        ?PsrHttpClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?float $timeoutSeconds = null,
        ?string $userAgent = null,
    ) {
        $resolvedSecret = $this->resolveSecretKey($secretKey);
        $resolvedBaseUrl = $baseUrl !== null && $baseUrl !== '' ? $baseUrl : self::DEFAULT_BASE_URL;

        [$resolvedHttpClient, $resolvedRequestFactory, $resolvedStreamFactory] = $this->resolveTransport(
            $httpClient,
            $requestFactory,
            $streamFactory,
            $timeoutSeconds,
        );

        $transport = new HttpClient(
            $resolvedSecret,
            $resolvedBaseUrl,
            $resolvedHttpClient,
            $resolvedRequestFactory,
            $resolvedStreamFactory,
            $userAgent,
        );

        $this->sessions = new SessionsApi($transport);
        $this->fingerprints = new FingerprintsApi($transport);
        $this->teams = new TeamsApi($transport);
        $this->gate = new GateApi($transport);
        $this->webhooks = new WebhooksApi($transport);
    }

    public function sessions(): SessionsApi
    {
        return $this->sessions;
    }

    public function fingerprints(): FingerprintsApi
    {
        return $this->fingerprints;
    }

    public function teams(): TeamsApi
    {
        return $this->teams;
    }

    public function gate(): GateApi
    {
        return $this->gate;
    }

    public function webhooks(): WebhooksApi
    {
        return $this->webhooks;
    }

    private function resolveSecretKey(?string $secretKey): ?string
    {
        $resolved = $secretKey;
        if ($resolved === null || $resolved === '') {
            $resolved = getenv('TRIPWIRE_SECRET_KEY') ?: null;
        }

        return ($resolved === null || $resolved === '') ? null : $resolved;
    }

    /**
     * @return array{0: PsrHttpClientInterface, 1: RequestFactoryInterface, 2: StreamFactoryInterface}
     */
    private function resolveTransport(
        ?PsrHttpClientInterface $httpClient,
        ?RequestFactoryInterface $requestFactory,
        ?StreamFactoryInterface $streamFactory,
        ?float $timeoutSeconds,
    ): array {
        if ($httpClient === null && $requestFactory === null && $streamFactory === null) {
            $options = [
                'force_ip_resolve' => 'v4',
            ];
            if ($timeoutSeconds !== null) {
                $options['timeout'] = $timeoutSeconds;
            }

            $httpClient = new GuzzleClient($options);
            $factory = new Psr17Factory();
            $requestFactory = $factory;
            $streamFactory = $factory;
        }

        try {
            $resolvedHttpClient = $httpClient ?? Psr18ClientDiscovery::find();
        } catch (ClientExceptionInterface|\Throwable $exception) {
            throw new TripwireConfigurationError(
                'Unable to discover a PSR-18 HTTP client. Install a client like guzzlehttp/guzzle or pass one explicitly.',
            );
        }

        try {
            $resolvedRequestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
            $resolvedStreamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        } catch (\Throwable $exception) {
            throw new TripwireConfigurationError(
                'Unable to discover PSR-17 factories. Install nyholm/psr7 or pass requestFactory and streamFactory explicitly.',
            );
        }

        return [$resolvedHttpClient, $resolvedRequestFactory, $resolvedStreamFactory];
    }
}
