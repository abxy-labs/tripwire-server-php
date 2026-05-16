<?php

declare(strict_types=1);

namespace Foil\Server;

use JsonException;
use Throwable;
use Foil\Server\Exception\FoilConfigurationError;
use Foil\Server\Exception\FoilTokenVerificationError;
use Foil\Server\Resource\VerifiedFoilToken;
use Foil\Server\Result\SafeVerifyResult;

final class SealedToken
{
    private const VERSION = 0x01;

    public static function verify(string $sealedToken, ?string $secretKey = null): VerifiedFoilToken
    {
        $resolvedSecret = self::resolveSecretKey($secretKey);

        $buffer = base64_decode($sealedToken, true);
        if ($buffer === false || strlen($buffer) < 29) {
            throw new FoilTokenVerificationError('Foil token is too short.');
        }

        if (ord($buffer[0]) !== self::VERSION) {
            throw new FoilTokenVerificationError(
                sprintf('Unsupported Foil token version: %d', ord($buffer[0])),
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
            throw new FoilTokenVerificationError('Failed to verify Foil token.', $exception);
        }

        if (!is_string($plaintext)) {
            throw new FoilTokenVerificationError('Failed to verify Foil token.');
        }

        $inflated = zlib_decode($plaintext);
        if (!is_string($inflated)) {
            throw new FoilTokenVerificationError('Failed to verify Foil token.');
        }

        try {
            $payload = json_decode($inflated, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new FoilTokenVerificationError('Failed to verify Foil token.', $exception);
        }

        if (!is_array($payload)) {
            throw new FoilTokenVerificationError('Failed to verify Foil token.');
        }

        return VerifiedFoilToken::fromArray($payload);
    }

    public static function safeVerify(string $sealedToken, ?string $secretKey = null): SafeVerifyResult
    {
        try {
            return SafeVerifyResult::success(self::verify($sealedToken, $secretKey));
        } catch (FoilConfigurationError|FoilTokenVerificationError $exception) {
            return SafeVerifyResult::failure($exception);
        } catch (Throwable $exception) {
            return SafeVerifyResult::failure(new FoilTokenVerificationError('Failed to verify Foil token.', $exception));
        }
    }

    private static function resolveSecretKey(?string $secretKey): string
    {
        $resolved = $secretKey;
        if ($resolved === null || $resolved === '') {
            $resolved = getenv('FOIL_SECRET_KEY') ?: null;
        }

        if ($resolved === null || $resolved === '') {
            throw new FoilConfigurationError(
                'Missing Foil secret key. Pass secretKey explicitly or set FOIL_SECRET_KEY.',
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

