<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class Event
{
    /**
     * @param array<string, mixed> $data
     * @param array<int, WebhookDelivery> $webhook_deliveries
     */
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly string $type,
        public readonly EventSubject $subject,
        public readonly array $data,
        public readonly array $webhook_deliveries,
        public readonly string $created_at,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $deliveries = [];
        foreach ((array) ($data['webhook_deliveries'] ?? []) as $delivery) {
            if (is_array($delivery)) {
                $deliveries[] = WebhookDelivery::fromArray($delivery);
            }
        }

        return new self(
            (string) $data['object'],
            (string) $data['id'],
            (string) $data['type'],
            EventSubject::fromArray((array) $data['subject']),
            isset($data['data']) && is_array($data['data']) ? $data['data'] : [],
            $deliveries,
            (string) $data['created_at'],
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
            'type' => $this->type,
            'subject' => $this->subject->toArray(),
            'data' => $this->data,
            'webhook_deliveries' => array_map(
                static fn (WebhookDelivery $delivery): array => $delivery->toArray(),
                $this->webhook_deliveries,
            ),
            'created_at' => $this->created_at,
        ];
    }
}
