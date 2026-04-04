<?php

declare(strict_types=1);

namespace Tripwire\Server\Api;

use Tripwire\Server\Http\HttpClient;

final class GateApi
{
    private GateRegistryApi $registry;
    private GateServicesApi $services;
    private GateSessionsApi $sessions;
    private GateLoginSessionsApi $loginSessions;
    private GateAgentTokensApi $agentTokens;

    public function __construct(HttpClient $http)
    {
        $this->registry = new GateRegistryApi($http);
        $this->services = new GateServicesApi($http);
        $this->sessions = new GateSessionsApi($http);
        $this->loginSessions = new GateLoginSessionsApi($http);
        $this->agentTokens = new GateAgentTokensApi($http);
    }

    public function registry(): GateRegistryApi
    {
        return $this->registry;
    }

    public function services(): GateServicesApi
    {
        return $this->services;
    }

    public function sessions(): GateSessionsApi
    {
        return $this->sessions;
    }

    public function loginSessions(): GateLoginSessionsApi
    {
        return $this->loginSessions;
    }

    public function agentTokens(): GateAgentTokensApi
    {
        return $this->agentTokens;
    }
}
