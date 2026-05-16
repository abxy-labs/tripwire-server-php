<?php

declare(strict_types=1);

namespace Foil\Server\Api;

use Foil\Server\Http\HttpClient;
use Foil\Server\Resource\AgentTokenVerification;

final class GateAgentTokensApi
{
    public function __construct(private readonly HttpClient $http) {}

    public function verify(string $agentToken): AgentTokenVerification
    {
        $response = $this->http->requestJson(
            'POST',
            '/v1/gate/agent-tokens/verify',
            body: ['agent_token' => $agentToken],
        );
        return AgentTokenVerification::fromArray((array) $response['data']);
    }

    public function revoke(string $agentToken): void
    {
        $this->http->requestJson(
            'POST',
            '/v1/gate/agent-tokens/revoke',
            body: ['agent_token' => $agentToken],
            expectContent: false,
        );
    }
}
