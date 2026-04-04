<?php

declare(strict_types=1);

namespace Tripwire\Server\Tests\Unit;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Tripwire\Server\Client;
use Tripwire\Server\Exception\TripwireApiError;
use Tripwire\Server\Exception\TripwireConfigurationError;
use Tripwire\Server\Tests\Support\FixtureLoader;
use Tripwire\Server\Tests\Support\JsonResponse;
use Tripwire\Server\Tests\Support\TestHttpClient;

final class ClientTest extends TestCase
{
    public function testUsesEnvSecretKeyByDefault(): void
    {
        $original = getenv('TRIPWIRE_SECRET_KEY');
        putenv('TRIPWIRE_SECRET_KEY=sk_env_default');

        $fixture = FixtureLoader::load('api/sessions/list.json');
        $factory = new Psr17Factory();
        $httpClient = new TestHttpClient(
            static fn (RequestInterface $request) => JsonResponse::create($fixture),
        );

        try {
            $client = new Client(
                httpClient: $httpClient,
                requestFactory: $factory,
                streamFactory: $factory,
            );
            $result = $client->sessions()->list();
            self::assertCount(1, $result->items);
        } finally {
            if ($original !== false) {
                putenv('TRIPWIRE_SECRET_KEY=' . $original);
            } else {
                putenv('TRIPWIRE_SECRET_KEY');
            }
        }
    }

    public function testThrowsWhenNoSecretKeyIsConfigured(): void
    {
        $original = getenv('TRIPWIRE_SECRET_KEY');
        putenv('TRIPWIRE_SECRET_KEY');

        try {
            $factory = new Psr17Factory();
            $client = new Client(
                httpClient: new TestHttpClient(static fn (RequestInterface $request) => JsonResponse::create([])),
                requestFactory: $factory,
                streamFactory: $factory,
            );
            self::assertNotNull($client->gate());
        } finally {
            if ($original !== false) {
                putenv('TRIPWIRE_SECRET_KEY=' . $original);
            }
        }
    }

    public function testSecretEndpointsFailAtRequestTimeWhenNoSecretIsConfigured(): void
    {
        $original = getenv('TRIPWIRE_SECRET_KEY');
        putenv('TRIPWIRE_SECRET_KEY');

        try {
            $factory = new Psr17Factory();
            $client = new Client(
                httpClient: new TestHttpClient(static fn (RequestInterface $request) => JsonResponse::create([])),
                requestFactory: $factory,
                streamFactory: $factory,
            );
            $this->expectException(TripwireConfigurationError::class);
            $client->sessions()->list();
        } finally {
            if ($original !== false) {
                putenv('TRIPWIRE_SECRET_KEY=' . $original);
            }
        }
    }

    public function testAutodiscoveryCanBuildTheClient(): void
    {
        self::assertInstanceOf(Client::class, new Client(secretKey: 'sk_live_test'));
    }

    public function testListsSessionsWithNormalizedPaginationAndAuthHeaders(): void
    {
        $fixture = FixtureLoader::load('api/sessions/list.json');
        $factory = new Psr17Factory();
        $httpClient = new TestHttpClient(function (RequestInterface $request) use ($fixture) {
            self::assertSame('/v1/sessions', $request->getUri()->getPath());
            parse_str($request->getUri()->getQuery(), $query);
            self::assertSame('bot', $query['verdict']);
            self::assertSame('25', (string) $query['limit']);
            self::assertSame('Bearer sk_live_test', $request->getHeaderLine('Authorization'));
            self::assertSame('abxy/tripwire-server', $request->getHeaderLine('X-Tripwire-Client'));

            return JsonResponse::create($fixture);
        });

        $client = new Client(
            secretKey: 'sk_live_test',
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
        );

        $result = $client->sessions()->list(verdict: 'bot', limit: 25);
        self::assertSame(50, $result->limit);
        self::assertTrue($result->has_more);
        self::assertSame('cur_sessions_page_2', $result->next_cursor);
        self::assertSame($fixture['data'][0]['id'], $result->items[0]->id);
    }

    public function testIteratesPaginatedSessionResults(): void
    {
        $firstPage = FixtureLoader::load('api/sessions/list.json');
        $secondPage = [
            'data' => [
                [
                    ...$firstPage['data'][0],
                    'id' => 'sid_123456789abcdefghjkmnpqrst',
                    'latest_decision' => [
                        ...$firstPage['data'][0]['latest_decision'],
                        'event_id' => 'evt_3456789abcdefghjkmnpqrstvw',
                        'evaluated_at' => '2026-03-24T20:01:05.000Z',
                    ],
                ],
            ],
            'pagination' => [
                'limit' => 50,
                'has_more' => false,
            ],
            'meta' => [
                'request_id' => 'req_0123456789abcdef0123456789abcdef',
            ],
        ];

        $factory = new Psr17Factory();
        $httpClient = new TestHttpClient(static function (RequestInterface $request) use ($firstPage, $secondPage) {
            parse_str($request->getUri()->getQuery(), $query);
            return JsonResponse::create(isset($query['cursor']) ? $secondPage : $firstPage);
        });

        $client = new Client(
            secretKey: 'sk_live_test',
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
        );

        $ids = [];
        foreach ($client->sessions()->iterate(verdict: 'human') as $item) {
            $ids[] = $item->id;
        }

        self::assertSame(['sid_0123456789abcdefghjkmnpqrs', 'sid_123456789abcdefghjkmnpqrst'], $ids);
    }

    public function testFetchesSessionDetailResource(): void
    {
        $fixture = FixtureLoader::load('api/sessions/detail.json');
        $factory = new Psr17Factory();
        $httpClient = new TestHttpClient(static function (RequestInterface $request) use ($fixture) {
            self::assertStringContainsString('/v1/sessions/sid_0123456789abcdefghjkmnpqrs', (string) $request->getUri());
            return JsonResponse::create($fixture);
        });

        $client = new Client(
            secretKey: 'sk_live_test',
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
        );

        self::assertSame($fixture['data']['id'], $client->sessions()->get('sid_0123456789abcdefghjkmnpqrs')->id);
    }

    public function testListsAndFetchesFingerprints(): void
    {
        $listFixture = FixtureLoader::load('api/fingerprints/list.json');
        $detailFixture = FixtureLoader::load('api/fingerprints/detail.json');
        $factory = new Psr17Factory();
        $httpClient = new TestHttpClient(static function (RequestInterface $request) use ($listFixture, $detailFixture) {
            if (str_contains((string) $request->getUri(), '/v1/fingerprints/vid_456789abcdefghjkmnpqrstvwx')) {
                return JsonResponse::create($detailFixture);
            }

            return JsonResponse::create($listFixture);
        });

        $client = new Client(
            secretKey: 'sk_live_test',
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
        );

        $page = $client->fingerprints()->list();
        self::assertFalse($page->has_more);
        self::assertSame($listFixture['data'][0]['id'], $page->items[0]->id);
        self::assertSame($detailFixture['data']['id'], $client->fingerprints()->get('vid_456789abcdefghjkmnpqrstvwx')->id);
    }

    public function testSupportsTeamsAndApiKeyManagementEndpoints(): void
    {
        $teamFixture = FixtureLoader::load('api/teams/team.json');
        $createKeyFixture = FixtureLoader::load('api/teams/api-key-create.json');
        $listKeyFixture = FixtureLoader::load('api/teams/api-key-list.json');
        $revokeKeyFixture = FixtureLoader::load('api/teams/api-key-revoke.json');
        $rotateKeyFixture = FixtureLoader::load('api/teams/api-key-rotate.json');

        $factory = new Psr17Factory();
        $httpClient = new TestHttpClient(static function (RequestInterface $request) use ($teamFixture, $createKeyFixture, $listKeyFixture, $revokeKeyFixture, $rotateKeyFixture) {
            $url = (string) $request->getUri();

            if (str_ends_with($url, '/api-keys/key_6789abcdefghjkmnpqrstvwxyz/rotations')) {
                return JsonResponse::create($rotateKeyFixture, 201);
            }
            if (str_ends_with($url, '/api-keys/key_6789abcdefghjkmnpqrstvwxyz')) {
                return JsonResponse::create($revokeKeyFixture);
            }
            if (str_ends_with($url, '/api-keys') && $request->getMethod() === 'POST') {
                return JsonResponse::create($createKeyFixture, 201);
            }
            if (str_ends_with($url, '/api-keys')) {
                return JsonResponse::create($listKeyFixture);
            }

            return JsonResponse::create($teamFixture);
        });

        $client = new Client(
            secretKey: 'sk_live_test',
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
        );

        self::assertSame($teamFixture['data']['id'], $client->teams()->get('team_56789abcdefghjkmnpqrstvwxy')->id);
        self::assertSame($teamFixture['data']['id'], $client->teams()->create('Example Team', 'example-team')->id);
        self::assertSame($teamFixture['data']['id'], $client->teams()->update('team_56789abcdefghjkmnpqrstvwxy', name: 'Example Team')->id);
        self::assertSame($createKeyFixture['data']['id'], $client->teams()->apiKeys()->create('team_56789abcdefghjkmnpqrstvwxy', name: 'Production')->id);
        self::assertSame($listKeyFixture['data'][0]['id'], $client->teams()->apiKeys()->list('team_56789abcdefghjkmnpqrstvwxy')->items[0]->id);
        self::assertSame(
            $revokeKeyFixture['data']['id'],
            $client->teams()->apiKeys()->revoke('team_56789abcdefghjkmnpqrstvwxy', 'key_6789abcdefghjkmnpqrstvwxyz')->id,
        );
        self::assertSame($rotateKeyFixture['data']['id'], $client->teams()->apiKeys()->rotate('team_56789abcdefghjkmnpqrstvwxy', 'key_6789abcdefghjkmnpqrstvwxyz')->id);
    }

    public function testSupportsGateEndpointsAcrossPublicBearerAndSecretFlows(): void
    {
        $registryListFixture = FixtureLoader::load('api/gate/registry-list.json');
        $registryDetailFixture = FixtureLoader::load('api/gate/registry-detail.json');
        $servicesListFixture = FixtureLoader::load('api/gate/services-list.json');
        $serviceDetailFixture = FixtureLoader::load('api/gate/service-detail.json');
        $serviceCreateFixture = FixtureLoader::load('api/gate/service-create.json');
        $serviceUpdateFixture = FixtureLoader::load('api/gate/service-update.json');
        $serviceDisableFixture = FixtureLoader::load('api/gate/service-disable.json');
        $sessionCreateFixture = FixtureLoader::load('api/gate/session-create.json');
        $sessionPollFixture = FixtureLoader::load('api/gate/session-poll.json');
        $sessionAckFixture = FixtureLoader::load('api/gate/session-ack.json');
        $loginCreateFixture = FixtureLoader::load('api/gate/login-session-create.json');
        $loginConsumeFixture = FixtureLoader::load('api/gate/login-session-consume.json');
        $agentVerifyFixture = FixtureLoader::load('api/gate/agent-token-verify.json');

        $factory = new Psr17Factory();
        $httpClient = new TestHttpClient(static function (RequestInterface $request) use (
            $registryListFixture,
            $registryDetailFixture,
            $servicesListFixture,
            $serviceDetailFixture,
            $serviceCreateFixture,
            $serviceUpdateFixture,
            $serviceDisableFixture,
            $sessionCreateFixture,
            $sessionPollFixture,
            $sessionAckFixture,
            $loginCreateFixture,
            $loginConsumeFixture,
            $agentVerifyFixture,
        ) {
            $path = $request->getUri()->getPath();
            $auth = $request->getHeaderLine('Authorization');

            if ($path === '/v1/gate/registry') {
                self::assertSame('', $auth);
                return JsonResponse::create($registryListFixture);
            }
            if ($path === '/v1/gate/registry/tripwire') {
                self::assertSame('', $auth);
                return JsonResponse::create($registryDetailFixture);
            }
            if ($path === '/v1/gate/services' && $request->getMethod() === 'GET') {
                self::assertSame('Bearer sk_live_test', $auth);
                return JsonResponse::create($servicesListFixture);
            }
            if ($path === '/v1/gate/services/tripwire' && $request->getMethod() === 'GET') {
                self::assertSame('Bearer sk_live_test', $auth);
                return JsonResponse::create($serviceDetailFixture);
            }
            if ($path === '/v1/gate/services' && $request->getMethod() === 'POST') {
                self::assertSame('Bearer sk_live_test', $auth);
                return JsonResponse::create($serviceCreateFixture, 201);
            }
            if ($path === '/v1/gate/services/acme_prod' && $request->getMethod() === 'PATCH') {
                self::assertSame('Bearer sk_live_test', $auth);
                return JsonResponse::create($serviceUpdateFixture);
            }
            if ($path === '/v1/gate/services/acme_prod' && $request->getMethod() === 'DELETE') {
                self::assertSame('Bearer sk_live_test', $auth);
                return JsonResponse::create($serviceDisableFixture);
            }
            if ($path === '/v1/gate/sessions') {
                self::assertSame('', $auth);
                return JsonResponse::create($sessionCreateFixture, 201);
            }
            if ($path === '/v1/gate/sessions/gate_0123456789abcdefghjkmnpqrs' && $request->getMethod() === 'GET') {
                self::assertSame('Bearer gtpoll_0123456789abcdefghjkmnpqrs', $auth);
                return JsonResponse::create($sessionPollFixture);
            }
            if ($path === '/v1/gate/sessions/gate_0123456789abcdefghjkmnpqrs/ack') {
                self::assertSame('Bearer gtpoll_0123456789abcdefghjkmnpqrs', $auth);
                return JsonResponse::create($sessionAckFixture);
            }
            if ($path === '/v1/gate/login-sessions') {
                self::assertSame('Bearer agt_0123456789abcdefghjkmnpqrs', $auth);
                return JsonResponse::create($loginCreateFixture, 201);
            }
            if ($path === '/v1/gate/login-sessions/consume') {
                self::assertSame('Bearer sk_live_test', $auth);
                return JsonResponse::create($loginConsumeFixture);
            }
            if ($path === '/v1/gate/agent-tokens/verify') {
                self::assertSame('Bearer sk_live_test', $auth);
                return JsonResponse::create($agentVerifyFixture);
            }
            if ($path === '/v1/gate/agent-tokens/revoke') {
                self::assertSame('Bearer sk_live_test', $auth);
                return JsonResponse::create(null, 204);
            }

            self::fail('Unexpected request ' . $request->getMethod() . ' ' . $path);
        });

        $client = new Client(
            secretKey: 'sk_live_test',
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
        );

        self::assertSame('tripwire', $client->gate()->registry()->list()[0]->id);
        self::assertSame('tripwire', $client->gate()->registry()->get('tripwire')->id);
        self::assertSame('acme_prod', $client->gate()->services()->list()[0]->id);
        self::assertSame('acme_prod', $client->gate()->services()->get('tripwire')->id);
        self::assertSame(
            'acme_prod',
            $client->gate()->services()->create(
                'acme_prod',
                'Acme Production',
                'Acme production signup flow',
                'https://acme.example.com',
                'https://api.acme.example.com/v1/gate/webhook',
            )->id,
        );
        self::assertTrue($client->gate()->services()->update('acme_prod', discoverable: true)->discoverable);
        self::assertSame('disabled', $client->gate()->services()->disable('acme_prod')->status);
        self::assertSame(
            'gate_0123456789abcdefghjkmnpqrs',
            $client->gate()->sessions()->create(
                'tripwire',
                'my-project',
                [
                    'version' => 1,
                    'algorithm' => 'x25519-hkdf-sha256/aes-256-gcm',
                    'key_id' => 'kid_integrator_0123456789abcdefgh',
                    'public_key' => 'public_key_integrator',
                ],
            )->id,
        );
        self::assertSame('approved', $client->gate()->sessions()->poll('gate_0123456789abcdefghjkmnpqrs', 'gtpoll_0123456789abcdefghjkmnpqrs')->status);
        self::assertSame('acknowledged', $client->gate()->sessions()->acknowledge('gate_0123456789abcdefghjkmnpqrs', 'gtpoll_0123456789abcdefghjkmnpqrs', 'gtack_0123456789abcdefghjkmnpqrs')->status);
        self::assertSame('gate_login_session', $client->gate()->loginSessions()->create('tripwire', 'agt_0123456789abcdefghjkmnpqrs')->object);
        self::assertSame('gate_dashboard_login', $client->gate()->loginSessions()->consume('gate_code_0123456789abcdefghjkm')->object);
        self::assertTrue($client->gate()->agentTokens()->verify('agt_0123456789abcdefghjkmnpqrs')->valid);
        self::assertNull($client->gate()->agentTokens()->revoke('agt_0123456789abcdefghjkmnpqrs'));
    }

    public function testParsesApiErrorsIntoTripwireApiError(): void
    {
        $fixture = FixtureLoader::load('errors/validation-error.json');
        $factory = new Psr17Factory();
        $httpClient = new TestHttpClient(static fn (RequestInterface $request) => JsonResponse::create(
            $fixture,
            $fixture['error']['status'],
            ['x-request-id' => $fixture['error']['request_id']],
        ));

        $client = new Client(
            secretKey: 'sk_live_test',
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
        );

        try {
            $client->sessions()->list(limit: 999);
            self::fail('Expected TripwireApiError to be thrown.');
        } catch (TripwireApiError $error) {
            self::assertSame(422, $error->status);
            self::assertSame($fixture['error']['code'], $error->code);
            self::assertSame($fixture['error']['request_id'], $error->request_id);
            self::assertSame($fixture['error']['details']['fields'], $error->field_errors);
            self::assertSame($fixture['error']['docs_url'] ?? null, $error->docs_url);
        }
    }
}
