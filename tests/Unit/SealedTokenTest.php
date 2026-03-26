<?php

declare(strict_types=1);

namespace Tripwire\Server\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tripwire\Server\Exception\TripwireConfigurationError;
use Tripwire\Server\Exception\TripwireTokenVerificationError;
use Tripwire\Server\SealedToken;
use Tripwire\Server\Tests\Support\FixtureLoader;

final class SealedTokenTest extends TestCase
{
    public function testVerifyVectorWithPlaintextSecret(): void
    {
        $fixture = FixtureLoader::load('sealed-token/vector.v1.json');
        $verified = SealedToken::verify($fixture['token'], $fixture['secretKey']);

        self::assertSame($fixture['payload']['session_id'], $verified->session_id);
        self::assertSame($fixture['payload'], $verified->toArray());
    }

    public function testVerifyVectorWithSecretHash(): void
    {
        $fixture = FixtureLoader::load('sealed-token/vector.v1.json');
        $verified = SealedToken::verify($fixture['token'], $fixture['secretHash']);

        self::assertSame($fixture['payload'], $verified->toArray());
    }

    public function testInvalidTokenReturnsFailureResult(): void
    {
        $fixture = FixtureLoader::load('sealed-token/invalid.json');
        $result = SealedToken::safeVerify($fixture['token'], 'sk_live_fixture_secret');

        self::assertFalse($result->ok);
        self::assertInstanceOf(TripwireTokenVerificationError::class, $result->error);
    }

    public function testMissingSecretRaisesConfigurationError(): void
    {
        $fixture = FixtureLoader::load('sealed-token/vector.v1.json');
        $original = getenv('TRIPWIRE_SECRET_KEY');
        putenv('TRIPWIRE_SECRET_KEY');

        try {
            $this->expectException(TripwireConfigurationError::class);
            SealedToken::verify($fixture['token']);
        } finally {
            if ($original !== false) {
                putenv('TRIPWIRE_SECRET_KEY=' . $original);
            } else {
                putenv('TRIPWIRE_SECRET_KEY');
            }
        }
    }
}
