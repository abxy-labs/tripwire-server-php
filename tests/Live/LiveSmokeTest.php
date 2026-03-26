<?php

declare(strict_types=1);

namespace Tripwire\Server\Tests\Live;

use PHPUnit\Framework\TestCase;
use Tripwire\Server\Client;
use Tripwire\Server\Exception\TripwireApiError;
use Tripwire\Server\Resource\ApiKey;
use Tripwire\Server\SealedToken;
use Tripwire\Server\Tests\Support\FixtureLoader;

final class LiveSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        if ((getenv('TRIPWIRE_LIVE_SMOKE') ?: '') !== '1') {
            $this->markTestSkipped('Set TRIPWIRE_LIVE_SMOKE=1 to run live smoke tests.');
        }
    }

    public function testPublicServerSurface(): void
    {
        $client = new Client(
            secretKey: $this->requireEnv('TRIPWIRE_SMOKE_SECRET_KEY'),
            baseUrl: getenv('TRIPWIRE_SMOKE_BASE_URL') ?: 'https://api.tripwirejs.com',
        );
        $teamId = $this->requireEnv('TRIPWIRE_SMOKE_TEAM_ID');

        $createdKeyId = null;
        $rotatedKeyId = null;

        try {
            $sessions = $client->sessions()->list(limit: 1);
            self::assertGreaterThan(0, count($sessions->items), 'Smoke team must have at least one session for the live smoke suite.');
            $session = $client->sessions()->get($sessions->items[0]->id);
            self::assertSame($sessions->items[0]->id, $session->id);

            $fingerprints = $client->fingerprints()->list(limit: 1);
            self::assertGreaterThan(0, count($fingerprints->items), 'Smoke team must have at least one fingerprint for the live smoke suite.');
            $fingerprint = $client->fingerprints()->get($fingerprints->items[0]->id);
            self::assertSame($fingerprints->items[0]->id, $fingerprint->id);

            $team = $client->teams()->get($teamId);
            self::assertSame($teamId, $team->id);
            $updatedTeam = $client->teams()->update($teamId, name: $team->name, status: $team->status);
            self::assertSame($team->name, $updatedTeam->name);
            self::assertSame($team->status, $updatedTeam->status);

            $created = $client->teams()->apiKeys()->create(
                $teamId,
                name: 'sdk-smoke-' . base_convert((string) (int) floor(microtime(true) * 1000), 10, 36),
                environment: 'test',
            );
            $createdKeyId = $created->id;
            self::assertStringStartsWith('sk_', $created->secret_key);

            $listedKey = $this->findApiKey($client, $teamId, $created->id);
            self::assertNotNull($listedKey);
            self::assertSame($created->id, $listedKey?->id);

            $rotated = $client->teams()->apiKeys()->rotate($teamId, $created->id);
            $rotatedKeyId = $rotated->id;
            self::assertStringStartsWith('sk_', $rotated->secret_key);

            $fixture = FixtureLoader::load('sealed-token/vector.v1.json');
            $verified = SealedToken::safeVerify($fixture['token'], $fixture['secretKey']);
            self::assertTrue($verified->ok);
            self::assertSame($fixture['payload']['session_id'], $verified->data?->session_id);
        } finally {
            $this->bestEffortRevoke($client, $teamId, $rotatedKeyId);
            if ($createdKeyId !== $rotatedKeyId) {
                $this->bestEffortRevoke($client, $teamId, $createdKeyId);
            }
        }
    }

    private function requireEnv(string $name): string
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            throw new \RuntimeException($name . ' is required for the live smoke suite.');
        }

        return $value;
    }

    private function bestEffortRevoke(Client $client, string $teamId, ?string $keyId): void
    {
        if ($keyId === null || $keyId === '') {
            return;
        }

        try {
            $client->teams()->apiKeys()->revoke($teamId, $keyId);
        } catch (TripwireApiError $error) {
            if ($error->status === 404 || $error->code === 'request.not_found') {
                return;
            }

            throw $error;
        }
    }

    private function findApiKey(Client $client, string $teamId, string $keyId): ?ApiKey
    {
        $cursor = null;

        do {
            $page = $client->teams()->apiKeys()->list($teamId, limit: 100, cursor: $cursor);
            foreach ($page->items as $item) {
                if ($item->id === $keyId) {
                    return $item;
                }
            }
            $cursor = $page->has_more ? $page->next_cursor : null;
        } while ($cursor !== null);

        return null;
    }
}
