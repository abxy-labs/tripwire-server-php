<?php

declare(strict_types=1);

namespace Tripwire\Server\Tests\Contract;

use PHPUnit\Framework\TestCase;

final class ContractTest extends TestCase
{
    private function readSpec(): array
    {
        return json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/spec/openapi.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    public function testOnlySupportedPublicPathsAreExposed(): void
    {
        $spec = $this->readSpec();
        $paths = array_keys($spec['paths']);
        sort($paths);

        self::assertSame(
            [
                '/v1/fingerprints',
                '/v1/fingerprints/{visitorId}',
                '/v1/gate/agent-tokens/revoke',
                '/v1/gate/agent-tokens/verify',
                '/v1/gate/login-sessions',
                '/v1/gate/login-sessions/consume',
                '/v1/gate/registry',
                '/v1/gate/registry/{serviceId}',
                '/v1/gate/services',
                '/v1/gate/services/{serviceId}',
                '/v1/gate/sessions',
                '/v1/gate/sessions/{gateSessionId}',
                '/v1/gate/sessions/{gateSessionId}/ack',
                '/v1/sessions',
                '/v1/sessions/{sessionId}',
                '/v1/teams',
                '/v1/teams/{teamId}',
                '/v1/teams/{teamId}/api-keys',
                '/v1/teams/{teamId}/api-keys/{keyId}',
                '/v1/teams/{teamId}/api-keys/{keyId}/rotations',
            ],
            $paths,
        );
    }

    public function testCollectEndpointsAreExcluded(): void
    {
        $spec = $this->readSpec();

        foreach (array_keys($spec['paths']) as $path) {
            self::assertFalse(str_starts_with($path, '/v1/collect/'));
        }
    }

    public function testExpectedSuccessFixturesExist(): void
    {
        $fixtures = [
            'api/sessions/list.json',
            'api/sessions/detail.json',
            'api/fingerprints/list.json',
            'api/fingerprints/detail.json',
            'api/gate/registry-list.json',
            'api/gate/registry-detail.json',
            'api/gate/services-list.json',
            'api/gate/service-detail.json',
            'api/gate/service-create.json',
            'api/gate/service-update.json',
            'api/gate/service-disable.json',
            'api/gate/session-create.json',
            'api/gate/session-poll.json',
            'api/gate/session-ack.json',
            'api/gate/login-session-create.json',
            'api/gate/login-session-consume.json',
            'api/gate/agent-token-verify.json',
            'api/gate/agent-token-revoke.json',
            'api/teams/team.json',
            'api/teams/team-create.json',
            'api/teams/team-update.json',
            'api/teams/api-key-create.json',
            'api/teams/api-key-list.json',
            'api/teams/api-key-rotate.json',
            'api/teams/api-key-revoke.json',
        ];

        foreach ($fixtures as $relativePath) {
            self::assertFileExists(dirname(__DIR__, 2) . '/spec/fixtures/' . $relativePath);
        }
    }

    public function testCriticalSchemaConstraintsAreTightened(): void
    {
        $schemas = $this->readSpec()['components']['schemas'];

        self::assertSame('^sid_[0123456789abcdefghjkmnpqrstvwxyz]{26}$', $schemas['SessionId']['pattern']);
        self::assertSame('^vid_[0123456789abcdefghjkmnpqrstvwxyz]{26}$', $schemas['FingerprintId']['pattern']);
        self::assertSame('^team_[0123456789abcdefghjkmnpqrstvwxyz]{26}$', $schemas['TeamId']['pattern']);
        self::assertSame('^key_[0123456789abcdefghjkmnpqrstvwxyz]{26}$', $schemas['ApiKeyId']['pattern']);

        self::assertSame(['$ref' => '#/components/schemas/SessionId'], $schemas['SessionSummary']['properties']['id']);
        self::assertSame(['$ref' => '#/components/schemas/TeamStatus'], $schemas['Team']['properties']['status']);
        self::assertSame(['$ref' => '#/components/schemas/ApiKeyStatus'], $schemas['ApiKey']['properties']['status']);
        self::assertSame(
            '#/components/schemas/KnownPublicErrorCode',
            $schemas['PublicError']['properties']['code']['x-tripwire-known-values-ref'],
        );
        self::assertSame(['active', 'suspended', 'deleted'], $schemas['TeamStatus']['enum']);
        self::assertSame(['active', 'revoked', 'rotated'], $schemas['ApiKeyStatus']['enum']);
        self::assertContains('decision', $schemas['SessionDetail']['required']);
        self::assertContains('highlights', $schemas['SessionDetail']['required']);
        self::assertContains('automation', $schemas['SessionDetail']['required']);
        self::assertContains('web_bot_auth', $schemas['SessionDetail']['required']);
        self::assertContains('runtime_integrity', $schemas['SessionDetail']['required']);
        self::assertContains('visitor_fingerprint', $schemas['SessionDetail']['required']);
        self::assertContains('connection_fingerprint', $schemas['SessionDetail']['required']);
        self::assertContains('previous_decisions', $schemas['SessionDetail']['required']);
        self::assertContains('request', $schemas['SessionDetail']['required']);
        self::assertContains('browser', $schemas['SessionDetail']['required']);
        self::assertContains('device', $schemas['SessionDetail']['required']);
        self::assertContains('network', $schemas['SessionDetail']['required']);
        self::assertContains('analysis_coverage', $schemas['SessionDetail']['required']);
        self::assertContains('signals_fired', $schemas['SessionDetail']['required']);
        self::assertContains('client_telemetry', $schemas['SessionDetail']['required']);
        self::assertSame(
            ['$ref' => '#/components/schemas/SessionDetailRequest'],
            $schemas['SessionDetail']['properties']['request'],
        );
        self::assertSame(
            ['$ref' => '#/components/schemas/SessionClientTelemetry'],
            $schemas['SessionDetail']['properties']['client_telemetry'],
        );
        self::assertSame(
            ['anyOf' => [['$ref' => '#/components/schemas/SessionAutomation'], ['type' => 'null']]],
            $schemas['SessionDetail']['properties']['automation'],
        );
        self::assertSame(
            ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/SessionSignalFired']],
            $schemas['SessionDetail']['properties']['signals_fired'],
        );
        self::assertSame('string', $schemas['SessionSignalFired']['properties']['signal']['type']);
        self::assertContains('allowed_origins', $schemas['ApiKey']['required']);
        self::assertContains('rate_limit', $schemas['ApiKey']['required']);
        self::assertContains('rotated_at', $schemas['ApiKey']['required']);
        self::assertContains('revoked_at', $schemas['ApiKey']['required']);
        self::assertArrayNotHasKey('team_id', $schemas['GateManagedService']['properties']);
        self::assertArrayNotHasKey('webhook_secret', $schemas['GateManagedService']['properties']);
        self::assertArrayNotHasKey('CollectBatchResponse', $schemas);
    }

    public function testPublicOperationsHaveStableIDsAndTags(): void
    {
        $paths = $this->readSpec()['paths'];

        self::assertSame('listSessions', $paths['/v1/sessions']['get']['operationId']);
        self::assertSame(['Sessions'], $paths['/v1/sessions']['get']['tags']);
        self::assertSame('getVisitorFingerprint', $paths['/v1/fingerprints/{visitorId}']['get']['operationId']);
        self::assertSame(['Visitor fingerprints'], $paths['/v1/fingerprints/{visitorId}']['get']['tags']);
        self::assertSame('updateTeam', $paths['/v1/teams/{teamId}']['patch']['operationId']);
        self::assertSame(['Teams'], $paths['/v1/teams/{teamId}']['patch']['tags']);
        self::assertSame(
            'rotateTeamApiKey',
            $paths['/v1/teams/{teamId}/api-keys/{keyId}/rotations']['post']['operationId'],
        );
        self::assertSame(['API Keys'], $paths['/v1/teams/{teamId}/api-keys/{keyId}/rotations']['post']['tags']);
        self::assertSame('createManagedGateService', $paths['/v1/gate/services']['post']['operationId']);
        self::assertSame(['Gate'], $paths['/v1/gate/services']['post']['tags']);
        self::assertSame('pollGateSession', $paths['/v1/gate/sessions/{gateSessionId}']['get']['operationId']);
        self::assertSame(['Gate'], $paths['/v1/gate/sessions/{gateSessionId}']['get']['tags']);
        self::assertSame('revokeGateAgentToken', $paths['/v1/gate/agent-tokens/revoke']['post']['operationId']);
        self::assertSame(['Gate'], $paths['/v1/gate/agent-tokens/revoke']['post']['tags']);
    }
}
