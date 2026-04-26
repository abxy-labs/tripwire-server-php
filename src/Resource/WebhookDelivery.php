<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class WebhookDelivery
{
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly string $event_id,
        public readonly string $endpoint_id,
        public readonly string $event_type,
        public readonly string $status,
        public readonly int $attempts,
        public readonly ?int $response_status,
        public readonly ?string $response_body,
        public readonly ?string $error,
        public readonly string $created_at,
        public readonly string $updated_at,
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
            (string) $data['event_id'],
            (string) $data['endpoint_id'],
            (string) $data['event_type'],
            (string) $data['status'],
            (int) $data['attempts'],
            isset($data['response_status']) ? (int) $data['response_status'] : null,
            isset($data['response_body']) ? (string) $data['response_body'] : null,
            isset($data['error']) ? (string) $data['error'] : null,
            (string) $data['created_at'],
            (string) $data['updated_at'],
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
            'event_id' => $this->event_id,
            'endpoint_id' => $this->endpoint_id,
            'event_type' => $this->event_type,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'response_status' => $this->response_status,
            'response_body' => $this->response_body,
            'error' => $this->error,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
