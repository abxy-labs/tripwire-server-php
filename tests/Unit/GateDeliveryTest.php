<?php

declare(strict_types=1);

namespace Tripwire\Server\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tripwire\Server\GateDelivery;
use Tripwire\Server\Tests\Support\FixtureLoader;

final class GateDeliveryTest extends TestCase
{
    public function testDeliveryRequestAndVectorFixtures(): void
    {
        $requestFixture = FixtureLoader::load('gate-delivery/delivery-request.json');
        $vectorFixture = FixtureLoader::load('gate-delivery/vector.v1.json');

        $validated = GateDelivery::validateGateDeliveryRequest($requestFixture['delivery']);
        self::assertSame($requestFixture['derived_key_id'], $validated['key_id']);

        $privateKey = GateDelivery::importDeliveryPrivateKeyPkcs8($vectorFixture['private_key_pkcs8']);
        $decrypted = GateDelivery::decryptGateDeliveryEnvelope($privateKey, $vectorFixture['envelope']);
        self::assertSame($vectorFixture['payload']['outputs'], $decrypted['outputs']);
        self::assertSame($vectorFixture['payload']['ack_token'], $decrypted['ack_token']);
    }

    public function testWebhookSignatureAndEnvPolicyFixtures(): void
    {
        $payloadFixture = FixtureLoader::load('gate-delivery/approved-webhook-payload.valid.json');
        $signatureFixture = FixtureLoader::load('gate-delivery/webhook-signature.json');
        $envPolicyFixture = FixtureLoader::load('gate-delivery/env-policy.json');

        $validated = GateDelivery::validateGateApprovedWebhookPayload($payloadFixture);
        self::assertSame($payloadFixture['service_id'], $validated['service_id']);
        self::assertSame($payloadFixture['gate_session_id'], $validated['gate_session_id']);

        self::assertTrue(GateDelivery::verifyGateWebhookSignature(
            $signatureFixture['secret'],
            $signatureFixture['timestamp'],
            $signatureFixture['raw_body'],
            $signatureFixture['signature'],
            nowSeconds: $signatureFixture['now_seconds'],
        ));
        self::assertFalse(GateDelivery::verifyGateWebhookSignature(
            $signatureFixture['secret'],
            $signatureFixture['timestamp'],
            $signatureFixture['raw_body'],
            $signatureFixture['invalid_signature'],
            nowSeconds: $signatureFixture['now_seconds'],
        ));
        self::assertFalse(GateDelivery::verifyGateWebhookSignature(
            $signatureFixture['secret'],
            $signatureFixture['expired_timestamp'],
            $signatureFixture['raw_body'],
            $signatureFixture['signature'],
            nowSeconds: $signatureFixture['now_seconds'],
        ));

        foreach ($envPolicyFixture['derive_agent_token_env_key'] as $entry) {
            self::assertSame($entry['expected'], GateDelivery::deriveGateAgentTokenEnvKey($entry['service_id']));
        }
        foreach ($envPolicyFixture['is_gate_managed_env_var_key'] as $entry) {
            self::assertSame($entry['managed'], GateDelivery::isGateManagedEnvVarKey($entry['key']));
        }
        foreach ($envPolicyFixture['is_blocked_gate_env_var_key'] as $entry) {
            self::assertSame($entry['blocked'], GateDelivery::isBlockedGateEnvVarKey($entry['key']));
        }
    }

    public function testCreatedResponseRoundtrips(): void
    {
        $keyPair = GateDelivery::createDeliveryKeyPair();
        $response = GateDelivery::createGateApprovedWebhookResponse([
            'delivery' => $keyPair['delivery'],
            'outputs' => [
                'TRIPWIRE_PUBLISHABLE_KEY' => 'pk_live_bundle',
                'TRIPWIRE_SECRET_KEY' => 'sk_live_bundle',
            ],
        ]);
        $decrypted = GateDelivery::decryptGateDeliveryEnvelope($keyPair['private_key'], $response['encrypted_delivery']);
        self::assertSame(
            [
                'TRIPWIRE_PUBLISHABLE_KEY' => 'pk_live_bundle',
                'TRIPWIRE_SECRET_KEY' => 'sk_live_bundle',
            ],
            $decrypted['outputs'],
        );
    }
}
