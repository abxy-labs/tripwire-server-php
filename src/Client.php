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
use Tripwire\Server\Api\SessionsApi;
use Tripwire\Server\Api\TeamsApi;
use Tripwire\Server\Exception\TripwireConfigurationError;
use Tripwire\Server\Http\HttpClient;

final class Client
{
    private const DEFAULT_BASE_URL = 'https://api.tripwirejs.com';

    private SessionsApi $sessions;
    private FingerprintsApi $fingerprints;
    private TeamsApi $teams;

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

    private function resolveSecretKey(?string $secretKey): string
    {
        $resolved = $secretKey;
        if ($resolved === null || $resolved === '') {
            $resolved = getenv('TRIPWIRE_SECRET_KEY') ?: null;
        }

        if ($resolved === null || $resolved === '') {
            throw new TripwireConfigurationError(
                'Missing Tripwire secret key. Pass secretKey explicitly or set TRIPWIRE_SECRET_KEY.',
            );
        }

        return $resolved;
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
        if ($httpClient === null && $timeoutSeconds !== null) {
            $httpClient = new GuzzleClient(['timeout' => $timeoutSeconds]);
        }

        if ($requestFactory === null && $streamFactory === null && $timeoutSeconds !== null) {
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
