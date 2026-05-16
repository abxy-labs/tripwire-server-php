<?php

declare(strict_types=1);

namespace Foil\Server\Api;

use Foil\Server\Http\HttpClient;
use Foil\Server\Resource\GateSessionCreate;
use Foil\Server\Resource\GateSessionDeliveryAcknowledgement;
use Foil\Server\Resource\GateSessionPollData;

final class GateSessionsApi
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * @param array<string, mixed> $delivery
     * @param array<string, mixed>|null $metadata
     */
    public function create(
        string $serviceId,
        string $accountName,
        array $delivery,
        ?array $metadata = null,
    ): GateSessionCreate {
        $body = [
            'service_id' => $serviceId,
            'account_name' => $accountName,
            'delivery' => $delivery,
        ];
        if ($metadata !== null) {
            $body['metadata'] = $metadata;
        }

        $response = $this->http->requestJson(
            'POST',
            '/v1/gate/sessions',
            body: $body,
            authMode: HttpClient::AUTH_NONE,
        );
        return GateSessionCreate::fromArray((array) $response['data']);
    }

    public function poll(string $gateSessionId, string $pollToken): GateSessionPollData
    {
        $response = $this->http->requestJson(
            'GET',
            '/v1/gate/sessions/' . rawurlencode($gateSessionId),
            authMode: HttpClient::AUTH_BEARER,
            bearerToken: $pollToken,
        );
        return GateSessionPollData::fromArray((array) $response['data']);
    }

    public function acknowledge(
        string $gateSessionId,
        string $pollToken,
        string $ackToken,
    ): GateSessionDeliveryAcknowledgement {
        $response = $this->http->requestJson(
            'POST',
            '/v1/gate/sessions/' . rawurlencode($gateSessionId) . '/ack',
            body: ['ack_token' => $ackToken],
            authMode: HttpClient::AUTH_BEARER,
            bearerToken: $pollToken,
        );
        return GateSessionDeliveryAcknowledgement::fromArray((array) $response['data']);
    }
}
