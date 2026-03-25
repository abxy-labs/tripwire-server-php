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
            'public-api/sessions/list.json',
            'public-api/sessions/detail.json',
            'public-api/fingerprints/list.json',
            'public-api/fingerprints/detail.json',
            'public-api/teams/team.json',
            'public-api/teams/team-create.json',
            'public-api/teams/team-update.json',
            'public-api/teams/api-key-create.json',
            'public-api/teams/api-key-list.json',
            'public-api/teams/api-key-rotate.json',
            'public-api/teams/api-key-revoke.json',
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
        self::assertContains('ipIntel', $schemas['SessionDetail']['required']);
        self::assertContains('allowedOrigins', $schemas['ApiKey']['required']);
        self::assertContains('rateLimit', $schemas['ApiKey']['required']);
        self::assertContains('rotatedAt', $schemas['ApiKey']['required']);
        self::assertContains('revokedAt', $schemas['ApiKey']['required']);
        self::assertArrayNotHasKey('CollectBatchResponse', $schemas);
    }

    public function testPublicOperationsHaveStableIDsAndTags(): void
    {
        $paths = $this->readSpec()['paths'];

        self::assertSame('listSessions', $paths['/v1/sessions']['get']['operationId']);
        self::assertSame(['Sessions'], $paths['/v1/sessions']['get']['tags']);
        self::assertSame('getFingerprint', $paths['/v1/fingerprints/{visitorId}']['get']['operationId']);
        self::assertSame(['Fingerprints'], $paths['/v1/fingerprints/{visitorId}']['get']['tags']);
        self::assertSame('updateTeam', $paths['/v1/teams/{teamId}']['patch']['operationId']);
        self::assertSame(['Teams'], $paths['/v1/teams/{teamId}']['patch']['tags']);
        self::assertSame(
            'rotateTeamApiKey',
            $paths['/v1/teams/{teamId}/api-keys/{keyId}/rotations']['post']['operationId'],
        );
        self::assertSame(['API Keys'], $paths['/v1/teams/{teamId}/api-keys/{keyId}/rotations']['post']['tags']);
    }
}
