<?php

declare(strict_types=1);

namespace Tripwire\Server;

use JsonException;
use Throwable;
use Tripwire\Server\Exception\TripwireConfigurationError;
use Tripwire\Server\Exception\TripwireTokenVerificationError;
use Tripwire\Server\Resource\VerifiedTripwireToken;
use Tripwire\Server\Result\SafeVerifyResult;

final class SealedToken
{
    private const VERSION = 0x01;

    public static function verify(string $sealedToken, ?string $secretKey = null): VerifiedTripwireToken
    {
        $resolvedSecret = self::resolveSecretKey($secretKey);

        $buffer = base64_decode($sealedToken, true);
        if ($buffer === false || strlen($buffer) < 29) {
            throw new TripwireTokenVerificationError('Tripwire token is too short.');
        }

        if (ord($buffer[0]) !== self::VERSION) {
            throw new TripwireTokenVerificationError(
                sprintf('Unsupported Tripwire token version: %d', ord($buffer[0])),
            );
        }

        $nonce = substr($buffer, 1, 12);
        $tag = substr($buffer, -16);
        $ciphertext = substr($buffer, 13, -16);

        try {
            $plaintext = openssl_decrypt(
                $ciphertext,
                'aes-256-gcm',
                self::deriveKey($resolvedSecret),
                OPENSSL_RAW_DATA,
                $nonce,
                $tag,
            );
        } catch (Throwable $exception) {
            throw new TripwireTokenVerificationError('Failed to verify Tripwire token.', $exception);
        }

        if (!is_string($plaintext)) {
            throw new TripwireTokenVerificationError('Failed to verify Tripwire token.');
        }

        $inflated = zlib_decode($plaintext);
        if (!is_string($inflated)) {
            throw new TripwireTokenVerificationError('Failed to verify Tripwire token.');
        }

        try {
            $payload = json_decode($inflated, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new TripwireTokenVerificationError('Failed to verify Tripwire token.', $exception);
        }

        if (!is_array($payload)) {
            throw new TripwireTokenVerificationError('Failed to verify Tripwire token.');
        }

        return VerifiedTripwireToken::fromArray($payload);
    }

    public static function safeVerify(string $sealedToken, ?string $secretKey = null): SafeVerifyResult
    {
        try {
            return SafeVerifyResult::success(self::verify($sealedToken, $secretKey));
        } catch (TripwireConfigurationError|TripwireTokenVerificationError $exception) {
            return SafeVerifyResult::failure($exception);
        } catch (Throwable $exception) {
            return SafeVerifyResult::failure(new TripwireTokenVerificationError('Failed to verify Tripwire token.', $exception));
        }
    }

    private static function resolveSecretKey(?string $secretKey): string
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

    private static function deriveKey(string $secretKeyOrHash): string
    {
        $normalized = preg_match('/^[0-9a-f]{64}$/i', $secretKeyOrHash) === 1
            ? strtolower($secretKeyOrHash)
            : hash('sha256', $secretKeyOrHash);

        return hash('sha256', $normalized . "\0sealed-results", true);
    }
}

