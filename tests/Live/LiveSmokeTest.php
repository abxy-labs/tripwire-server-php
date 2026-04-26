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
        $organizationId = $this->requireEnv('TRIPWIRE_SMOKE_ORGANIZATION_ID');

        $createdKeyId = null;
        $rotatedKeyId = null;

        try {
            $sessions = $client->sessions()->list(limit: 1);
            self::assertGreaterThan(0, count($sessions->items), 'Smoke organization must have at least one session for the live smoke suite.');
            $session = $client->sessions()->get($sessions->items[0]->id);
            self::assertSame($sessions->items[0]->id, $session->id);

            $fingerprints = $client->fingerprints()->list(limit: 1);
            self::assertGreaterThan(0, count($fingerprints->items), 'Smoke organization must have at least one fingerprint for the live smoke suite.');
            $fingerprint = $client->fingerprints()->get($fingerprints->items[0]->id);
            self::assertSame($fingerprints->items[0]->id, $fingerprint->id);

            $organization = $client->organizations()->get($organizationId);
            self::assertSame($organizationId, $organization->id);
            $updatedOrganization = $client->organizations()->update($organizationId, name: $organization->name, status: $organization->status);
            self::assertSame($organization->name, $updatedOrganization->name);
            self::assertSame($organization->status, $updatedOrganization->status);

            $created = $client->organizations()->apiKeys()->create(
                $organizationId,
                name: 'sdk-smoke-' . base_convert((string) (int) floor(microtime(true) * 1000), 10, 36),
                environment: 'test',
            );
            $createdKeyId = $created->id;
            self::assertStringStartsWith('sk_', $created->revealed_key);

            $listedKey = $this->findApiKey($client, $organizationId, $created->id);
            self::assertNotNull($listedKey);
            self::assertSame($created->id, $listedKey?->id);

            $rotated = $client->organizations()->apiKeys()->rotate($organizationId, $created->id);
            $rotatedKeyId = $rotated->id;
            self::assertStringStartsWith('sk_', $rotated->revealed_key);

            $fixture = FixtureLoader::load('sealed-token/vector.v1.json');
            $verified = SealedToken::safeVerify($fixture['token'], $fixture['secretKey']);
            self::assertTrue($verified->ok);
            self::assertSame($fixture['payload']['session_id'], $verified->data?->session_id);
        } finally {
            $this->bestEffortRevoke($client, $organizationId, $rotatedKeyId);
            if ($createdKeyId !== $rotatedKeyId) {
                $this->bestEffortRevoke($client, $organizationId, $createdKeyId);
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

    private function bestEffortRevoke(Client $client, string $organizationId, ?string $keyId): void
    {
        if ($keyId === null || $keyId === '') {
            return;
        }

        try {
            $client->organizations()->apiKeys()->revoke($organizationId, $keyId);
        } catch (TripwireApiError $error) {
            if ($error->status === 404 || $error->code === 'request.not_found') {
                return;
            }

            throw $error;
        }
    }

    private function findApiKey(Client $client, string $organizationId, string $keyId): ?ApiKey
    {
        $cursor = null;

        do {
            $page = $client->organizations()->apiKeys()->list($organizationId, limit: 100, cursor: $cursor);
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
