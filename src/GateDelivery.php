<?php

declare(strict_types=1);

namespace Tripwire\Server;

use JsonException;
use Tripwire\Server\Resource\GateDeliveryEnvelope;
use Tripwire\Server\Resource\GateDeliveryPrivateKey;

final class GateDelivery
{
    public const GATE_DELIVERY_VERSION = 1;
    public const GATE_DELIVERY_ALGORITHM = 'x25519-hkdf-sha256/aes-256-gcm';
    public const GATE_AGENT_TOKEN_ENV_SUFFIX = '_GATE_AGENT_TOKEN';
    private const GATE_DELIVERY_HKDF_INFO = 'tripwire-gate-delivery:v1';
    private const X25519_PKCS8_PREFIX_HEX = '302e020100300506032b656e04220420';
    private const BLOCKED_GATE_ENV_VAR_KEYS = [
        'BASH_ENV',
        'BROWSER',
        'CDPATH',
        'DYLD_INSERT_LIBRARIES',
        'DYLD_LIBRARY_PATH',
        'EDITOR',
        'ENV',
        'GIT_ASKPASS',
        'GIT_SSH_COMMAND',
        'HOME',
        'LD_LIBRARY_PATH',
        'LD_PRELOAD',
        'NODE_OPTIONS',
        'NODE_PATH',
        'PATH',
        'PERL5OPT',
        'PERLLIB',
        'PROMPT_COMMAND',
        'PYTHONHOME',
        'PYTHONPATH',
        'PYTHONSTARTUP',
        'RUBYLIB',
        'RUBYOPT',
        'SHELLOPTS',
        'SSH_ASKPASS',
        'VISUAL',
        'XDG_CONFIG_HOME',
    ];
    private const BLOCKED_GATE_ENV_VAR_PREFIXES = [
        'NPM_CONFIG_',
        'BUN_CONFIG_',
        'GIT_CONFIG_',
    ];
    private const WEBHOOK_EVENT_TYPES = [
        'session.fingerprint.calculated' => true,
        'session.result.persisted' => true,
        'gate.session.approved' => true,
        'webhook.test' => true,
    ];

    public static function deriveGateAgentTokenEnvKey(string $serviceId): string
    {
        $normalized = trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', trim($serviceId)), '_');
        $normalized = strtoupper((string) preg_replace('/_+/', '_', $normalized));
        if ($normalized === '') {
            throw new \InvalidArgumentException('service_id is required to derive a Gate agent token env key');
        }

        return $normalized . self::GATE_AGENT_TOKEN_ENV_SUFFIX;
    }

    public static function isGateManagedEnvVarKey(string $key): bool
    {
        return $key === 'TRIPWIRE_AGENT_TOKEN' || str_ends_with($key, self::GATE_AGENT_TOKEN_ENV_SUFFIX);
    }

    public static function isBlockedGateEnvVarKey(string $key): bool
    {
        $normalized = strtoupper(trim($key));
        if (in_array($normalized, self::BLOCKED_GATE_ENV_VAR_KEYS, true)) {
            return true;
        }
        foreach (self::BLOCKED_GATE_ENV_VAR_PREFIXES as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{delivery: array<string, mixed>, private_key: GateDeliveryPrivateKey}
     */
    public static function createDeliveryKeyPair(): array
    {
        $secretKey = random_bytes(SODIUM_CRYPTO_BOX_SECRETKEYBYTES);
        $publicKey = sodium_crypto_scalarmult_base($secretKey);

        return [
            'delivery' => [
                'version' => self::GATE_DELIVERY_VERSION,
                'algorithm' => self::GATE_DELIVERY_ALGORITHM,
                'key_id' => self::keyIdForRawX25519PublicKey($publicKey),
                'public_key' => self::toBase64Url($publicKey),
            ],
            'private_key' => new GateDeliveryPrivateKey($secretKey),
        ];
    }

    public static function exportDeliveryPrivateKeyPkcs8(GateDeliveryPrivateKey $privateKey): string
    {
        return self::toBase64Url(hex2bin(self::X25519_PKCS8_PREFIX_HEX) . $privateKey->bytes);
    }

    public static function importDeliveryPrivateKeyPkcs8(string $value): GateDeliveryPrivateKey
    {
        $decoded = self::fromBase64Url($value, 'delivery.private_key_pkcs8');
        $prefix = hex2bin(self::X25519_PKCS8_PREFIX_HEX);
        if (!is_string($prefix) || !str_starts_with($decoded, $prefix) || strlen($decoded) !== strlen($prefix) + SODIUM_CRYPTO_BOX_SECRETKEYBYTES) {
            throw new \InvalidArgumentException('delivery.private_key_pkcs8 must contain an X25519 private key');
        }

        return new GateDeliveryPrivateKey(substr($decoded, strlen($prefix)));
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>
     */
    public static function validateGateDeliveryRequest(array $value): array
    {
        if (($value['version'] ?? null) !== self::GATE_DELIVERY_VERSION) {
            throw new \InvalidArgumentException('delivery.version must be 1');
        }
        if (($value['algorithm'] ?? null) !== self::GATE_DELIVERY_ALGORITHM) {
            throw new \InvalidArgumentException('delivery.algorithm must be ' . self::GATE_DELIVERY_ALGORITHM);
        }
        if (!is_string($value['public_key'] ?? null) || $value['public_key'] === '') {
            throw new \InvalidArgumentException('delivery.public_key is required');
        }
        if (!is_string($value['key_id'] ?? null) || $value['key_id'] === '') {
            throw new \InvalidArgumentException('delivery.key_id is required');
        }

        $rawPublicKey = self::fromBase64Url($value['public_key'], 'delivery.public_key');
        if (strlen($rawPublicKey) !== SODIUM_CRYPTO_BOX_PUBLICKEYBYTES) {
            throw new \InvalidArgumentException('delivery.public_key must be a raw X25519 public key');
        }
        if (self::keyIdForRawX25519PublicKey($rawPublicKey) !== $value['key_id']) {
            throw new \InvalidArgumentException('delivery.key_id does not match delivery.public_key');
        }

        return [
            'version' => self::GATE_DELIVERY_VERSION,
            'algorithm' => self::GATE_DELIVERY_ALGORITHM,
            'key_id' => $value['key_id'],
            'public_key' => $value['public_key'],
        ];
    }

    /**
     * @param array{delivery: array<string, mixed>, outputs: array<string, string>} $input
     * @return array{encrypted_delivery: array<string, mixed>}
     */
    public static function createEncryptedDeliveryResponse(array $input): array
    {
        return [
            'encrypted_delivery' => self::encryptGateDeliveryPayload(
                $input['delivery'],
                [
                    'version' => self::GATE_DELIVERY_VERSION,
                    'outputs' => $input['outputs'],
                ],
            ),
        ];
    }

    /**
     * @param array{delivery: array<string, mixed>, outputs: array<string, string>} $input
     * @return array{encrypted_delivery: array<string, mixed>}
     */
    public static function createGateApprovedWebhookResponse(array $input): array
    {
        return self::createEncryptedDeliveryResponse($input);
    }

    /**
     * @param array<string, mixed>|GateDeliveryEnvelope $value
     * @return array<string, mixed>
     */
    public static function validateEncryptedGateDeliveryEnvelope(array|GateDeliveryEnvelope $value): array
    {
        $candidate = $value instanceof GateDeliveryEnvelope ? [
            'version' => $value->version,
            'algorithm' => $value->algorithm,
            'key_id' => $value->key_id,
            'ephemeral_public_key' => $value->ephemeral_public_key,
            'salt' => $value->salt,
            'iv' => $value->iv,
            'ciphertext' => $value->ciphertext,
            'tag' => $value->tag,
        ] : $value;

        if (($candidate['version'] ?? null) !== self::GATE_DELIVERY_VERSION) {
            throw new \InvalidArgumentException('encrypted_delivery.version must be 1');
        }
        if (($candidate['algorithm'] ?? null) !== self::GATE_DELIVERY_ALGORITHM) {
            throw new \InvalidArgumentException('encrypted_delivery.algorithm must be ' . self::GATE_DELIVERY_ALGORITHM);
        }
        foreach (['key_id', 'ephemeral_public_key', 'salt', 'iv', 'ciphertext', 'tag'] as $field) {
            if (!is_string($candidate[$field] ?? null) || $candidate[$field] === '') {
                throw new \InvalidArgumentException(sprintf('encrypted_delivery.%s is required', $field));
            }
        }
        if (strlen(self::fromBase64Url($candidate['ephemeral_public_key'], 'encrypted_delivery.ephemeral_public_key')) !== 32) {
            throw new \InvalidArgumentException('encrypted_delivery.ephemeral_public_key must be 32 bytes');
        }
        if (strlen(self::fromBase64Url($candidate['salt'], 'encrypted_delivery.salt')) !== 32) {
            throw new \InvalidArgumentException('encrypted_delivery.salt must be 32 bytes');
        }
        if (strlen(self::fromBase64Url($candidate['iv'], 'encrypted_delivery.iv')) !== 12) {
            throw new \InvalidArgumentException('encrypted_delivery.iv must be 12 bytes');
        }
        if (strlen(self::fromBase64Url($candidate['tag'], 'encrypted_delivery.tag')) !== 16) {
            throw new \InvalidArgumentException('encrypted_delivery.tag must be 16 bytes');
        }

        return $candidate;
    }

    /**
     * @param array<string, mixed> $delivery
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function encryptGateDeliveryPayload(array $delivery, array $payload): array
    {
        $validatedDelivery = self::validateGateDeliveryRequest($delivery);
        if (($payload['version'] ?? null) !== self::GATE_DELIVERY_VERSION) {
            throw new \InvalidArgumentException('Gate delivery payload version must be 1');
        }
        if (!is_array($payload['outputs'] ?? null)) {
            throw new \InvalidArgumentException('encrypted_delivery payload outputs must be an object');
        }

        $recipientPublicKey = self::fromBase64Url($validatedDelivery['public_key'], 'delivery.public_key');
        $ephemeralSecretKey = random_bytes(SODIUM_CRYPTO_BOX_SECRETKEYBYTES);
        $ephemeralPublicKey = sodium_crypto_scalarmult_base($ephemeralSecretKey);
        $sharedSecret = sodium_crypto_scalarmult($ephemeralSecretKey, $recipientPublicKey);
        $salt = random_bytes(32);
        $iv = random_bytes(12);
        $key = hash_hkdf('sha256', $sharedSecret, 32, self::GATE_DELIVERY_HKDF_INFO, $salt);
        $ciphertext = openssl_encrypt(
            json_encode(self::compactPayload($payload), JSON_THROW_ON_ERROR),
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );
        if (!is_string($ciphertext) || !is_string($tag)) {
            throw new \InvalidArgumentException('failed to encrypt Gate delivery payload');
        }

        return [
            'version' => self::GATE_DELIVERY_VERSION,
            'algorithm' => self::GATE_DELIVERY_ALGORITHM,
            'key_id' => $validatedDelivery['key_id'],
            'ephemeral_public_key' => self::toBase64Url($ephemeralPublicKey),
            'salt' => self::toBase64Url($salt),
            'iv' => self::toBase64Url($iv),
            'ciphertext' => self::toBase64Url($ciphertext),
            'tag' => self::toBase64Url($tag),
        ];
    }

    /**
     * @param array<string, mixed>|GateDeliveryEnvelope $envelope
     * @return array<string, mixed>
     */
    public static function decryptGateDeliveryEnvelope(GateDeliveryPrivateKey $privateKey, array|GateDeliveryEnvelope $envelope): array
    {
        $validated = self::validateEncryptedGateDeliveryEnvelope($envelope);
        $sharedSecret = sodium_crypto_scalarmult(
            $privateKey->bytes,
            self::fromBase64Url($validated['ephemeral_public_key'], 'encrypted_delivery.ephemeral_public_key'),
        );
        $key = hash_hkdf(
            'sha256',
            $sharedSecret,
            32,
            self::GATE_DELIVERY_HKDF_INFO,
            self::fromBase64Url($validated['salt'], 'encrypted_delivery.salt'),
        );
        $plaintext = openssl_decrypt(
            self::fromBase64Url($validated['ciphertext'], 'encrypted_delivery.ciphertext'),
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            self::fromBase64Url($validated['iv'], 'encrypted_delivery.iv'),
            self::fromBase64Url($validated['tag'], 'encrypted_delivery.tag'),
        );
        if (!is_string($plaintext)) {
            throw new \InvalidArgumentException('encrypted_delivery decrypted to invalid JSON');
        }
        try {
            $payload = json_decode($plaintext, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \InvalidArgumentException('encrypted_delivery decrypted to invalid JSON', 0, $exception);
        }
        if (!is_array($payload)) {
            throw new \InvalidArgumentException('encrypted_delivery payload must be an object');
        }
        if (($payload['version'] ?? null) !== self::GATE_DELIVERY_VERSION) {
            throw new \InvalidArgumentException('encrypted_delivery payload version must be 1');
        }
        if (!is_array($payload['outputs'] ?? null)) {
            throw new \InvalidArgumentException('encrypted_delivery payload outputs must be an object');
        }
        foreach ($payload['outputs'] as $keyName => $value) {
            if (!is_string($value)) {
                throw new \InvalidArgumentException(sprintf('encrypted_delivery output %s must be a string', (string) $keyName));
            }
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>
     */
    public static function validateGateApprovedWebhookPayload(array $value): array
    {
        if (array_key_exists('event', $value)) {
            throw new \InvalidArgumentException('webhook payload must not include event; use the webhook event envelope type');
        }
        if (!is_string($value['service_id'] ?? null) || $value['service_id'] === '') {
            throw new \InvalidArgumentException('service_id is required');
        }
        if (!is_string($value['gate_session_id'] ?? null) || $value['gate_session_id'] === '') {
            throw new \InvalidArgumentException('gate_session_id is required');
        }
        if (!is_string($value['gate_account_id'] ?? null) || $value['gate_account_id'] === '') {
            throw new \InvalidArgumentException('gate_account_id is required');
        }
        if (!is_string($value['account_name'] ?? null) || $value['account_name'] === '') {
            throw new \InvalidArgumentException('account_name is required');
        }
        if (($value['metadata'] ?? null) !== null && !is_array($value['metadata'] ?? null)) {
            throw new \InvalidArgumentException('metadata must be an object or null');
        }
        if (!is_array($value['tripwire'] ?? null)) {
            throw new \InvalidArgumentException('tripwire must be an object');
        }
        if (!in_array($value['tripwire']['verdict'] ?? null, ['bot', 'human', 'inconclusive'], true)) {
            throw new \InvalidArgumentException('tripwire.verdict is invalid');
        }
        if (($value['tripwire']['score'] ?? null) !== null && !is_int($value['tripwire']['score']) && !is_float($value['tripwire']['score'])) {
            throw new \InvalidArgumentException('tripwire.score must be a number or null');
        }

        return [
            'service_id' => $value['service_id'],
            'gate_session_id' => $value['gate_session_id'],
            'gate_account_id' => $value['gate_account_id'],
            'account_name' => $value['account_name'],
            'metadata' => is_array($value['metadata'] ?? null) ? $value['metadata'] : null,
            'tripwire' => [
                'verdict' => $value['tripwire']['verdict'],
                'score' => isset($value['tripwire']['score']) ? (float) $value['tripwire']['score'] : null,
            ],
            'delivery' => self::validateGateDeliveryRequest($value['delivery'] ?? []),
        ];
    }

    public static function verifyGateWebhookSignature(
        string $secret,
        string $timestamp,
        string $rawBody,
        string $signature,
        int $maxAgeSeconds = 300,
        ?int $nowSeconds = null,
    ): bool {
        if (!preg_match('/^-?\d+$/', $timestamp)) {
            return false;
        }
        $parsedTimestamp = (int) $timestamp;
        $current = $nowSeconds ?? time();
        if (abs($current - $parsedTimestamp) > $maxAgeSeconds) {
            return false;
        }
        $expected = hash_hmac('sha256', sprintf('%s.%s', $timestamp, $rawBody), $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * @return array<string, mixed>
     */
    public static function parseWebhookEvent(string $rawBody): array
    {
        $value = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($value)) {
            throw new \InvalidArgumentException('webhook event envelope must be an object');
        }
        if (($value['object'] ?? null) !== 'webhook_event') {
            throw new \InvalidArgumentException('webhook event object must be webhook_event');
        }
        foreach (['id', 'type', 'created'] as $field) {
            if (!is_string($value[$field] ?? null) || $value[$field] === '') {
                throw new \InvalidArgumentException(sprintf('webhook event %s is required', $field));
            }
        }
        if (!isset(self::WEBHOOK_EVENT_TYPES[$value['type']])) {
            throw new \InvalidArgumentException(sprintf('unsupported webhook event type: %s', $value['type']));
        }
        if (!is_array($value['data'] ?? null)) {
            throw new \InvalidArgumentException('webhook event data must be an object');
        }
        if ($value['type'] === 'gate.session.approved') {
            $value['data'] = self::validateGateApprovedWebhookPayload($value['data']);
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public static function verifyAndParseWebhookEvent(
        string $secret,
        string $timestamp,
        string $rawBody,
        string $signature,
        int $maxAgeSeconds = 300,
        ?int $nowSeconds = null,
    ): array {
        if (!self::verifyGateWebhookSignature($secret, $timestamp, $rawBody, $signature, $maxAgeSeconds, $nowSeconds)) {
            throw new \InvalidArgumentException('Invalid Tripwire webhook signature');
        }

        return self::parseWebhookEvent($rawBody);
    }

    public static function keyIdForRawX25519PublicKey(string $rawPublicKey): string
    {
        if (strlen($rawPublicKey) !== 32) {
            throw new \InvalidArgumentException('X25519 public key must be 32 bytes');
        }

        return self::toBase64Url(hash('sha256', $rawPublicKey, true));
    }

    private static function compactPayload(array $payload): array
    {
        $result = [
            'version' => $payload['version'],
            'outputs' => $payload['outputs'],
        ];
        if (isset($payload['ack_token']) && is_string($payload['ack_token'])) {
            $result['ack_token'] = $payload['ack_token'];
        }

        return $result;
    }

    private static function toBase64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function fromBase64Url(string $value, string $label): string
    {
        $padding = str_repeat('=', (4 - strlen($value) % 4) % 4);
        $decoded = base64_decode(strtr($value . $padding, '-_', '+/'), true);
        if (!is_string($decoded)) {
            throw new \InvalidArgumentException(sprintf('invalid %s', $label));
        }

        return $decoded;
    }
}
