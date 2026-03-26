<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class SessionDetail
{
    /**
     * @param array<string, mixed> $decision
     * @param array<int, array<string, mixed>> $highlights
     * @param array<string, mixed>|null $automation
     * @param array<string, mixed>|null $web_bot_auth
     * @param array<string, mixed> $network
     * @param array<string, mixed> $runtime_integrity
     * @param array<string, mixed>|null $visitor_fingerprint
     * @param array<string, mixed> $connection_fingerprint
     * @param array<int, array<string, mixed>> $previous_decisions
     * @param array<string, mixed> $request
     * @param array<string, mixed> $browser
     * @param array<string, mixed> $device
     * @param array<string, mixed> $analysis_coverage
     * @param array<int, array<string, mixed>> $signals_fired
     * @param array<string, mixed> $client_telemetry
     */
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly ?string $created_at,
        public readonly array $decision,
        public readonly array $highlights,
        public readonly ?array $automation,
        public readonly ?array $web_bot_auth,
        public readonly array $network,
        public readonly array $runtime_integrity,
        public readonly ?array $visitor_fingerprint,
        public readonly array $connection_fingerprint,
        public readonly array $previous_decisions,
        public readonly array $request,
        public readonly array $browser,
        public readonly array $device,
        public readonly array $analysis_coverage,
        public readonly array $signals_fired,
        public readonly array $client_telemetry,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['object'],
            (string) $data['id'],
            isset($data['created_at']) ? (string) $data['created_at'] : null,
            (array) $data['decision'],
            array_map(static fn (mixed $item): array => (array) $item, (array) ($data['highlights'] ?? [])),
            isset($data['automation']) && is_array($data['automation']) ? $data['automation'] : null,
            isset($data['web_bot_auth']) && is_array($data['web_bot_auth']) ? $data['web_bot_auth'] : null,
            isset($data['network']) && is_array($data['network']) ? $data['network'] : [],
            isset($data['runtime_integrity']) && is_array($data['runtime_integrity']) ? $data['runtime_integrity'] : [],
            isset($data['visitor_fingerprint']) && is_array($data['visitor_fingerprint']) ? $data['visitor_fingerprint'] : null,
            isset($data['connection_fingerprint']) && is_array($data['connection_fingerprint']) ? $data['connection_fingerprint'] : [],
            array_map(static fn (mixed $item): array => (array) $item, (array) ($data['previous_decisions'] ?? [])),
            (array) $data['request'],
            isset($data['browser']) && is_array($data['browser']) ? $data['browser'] : [],
            isset($data['device']) && is_array($data['device']) ? $data['device'] : [],
            isset($data['analysis_coverage']) && is_array($data['analysis_coverage']) ? $data['analysis_coverage'] : [],
            array_map(static fn (mixed $item): array => (array) $item, (array) ($data['signals_fired'] ?? [])),
            isset($data['client_telemetry']) && is_array($data['client_telemetry']) ? $data['client_telemetry'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'object' => $this->object,
            'id' => $this->id,
            'created_at' => $this->created_at,
            'decision' => $this->decision,
            'highlights' => $this->highlights,
            'automation' => $this->automation,
            'web_bot_auth' => $this->web_bot_auth,
            'network' => $this->network,
            'runtime_integrity' => $this->runtime_integrity,
            'visitor_fingerprint' => $this->visitor_fingerprint,
            'connection_fingerprint' => $this->connection_fingerprint,
            'previous_decisions' => $this->previous_decisions,
            'request' => $this->request,
            'browser' => $this->browser,
            'device' => $this->device,
            'analysis_coverage' => $this->analysis_coverage,
            'signals_fired' => $this->signals_fired,
            'client_telemetry' => $this->client_telemetry,
        ];
    }
}
