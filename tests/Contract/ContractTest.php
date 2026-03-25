<?php

declare(strict_types=1);

namespace Tripwire\Server\Tests\Contract;

use PHPUnit\Framework\TestCase;

final class ContractTest extends TestCase
{
    public function testOnlySupportedPublicPathsAreExposed(): void
    {
        $spec = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/spec/openapi.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
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
        $spec = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/spec/openapi.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

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
}
