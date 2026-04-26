<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class WebhookTest
{
    /**
     * @param array<int, string> $delivery_ids
     */
    public function __construct(
        public readonly string $object,
        public readonly string $event_id,
        public readonly array $delivery_ids,
        public readonly ?WebhookDelivery $latest_delivery,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $latestDelivery = isset($data['latest_delivery']) && is_array($data['latest_delivery'])
            ? WebhookDelivery::fromArray($data['latest_delivery'])
            : null;

        return new self(
            (string) $data['object'],
            (string) $data['event_id'],
            array_map(static fn (mixed $value): string => (string) $value, (array) ($data['delivery_ids'] ?? [])),
            $latestDelivery,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'object' => $this->object,
            'event_id' => $this->event_id,
            'delivery_ids' => $this->delivery_ids,
            'latest_delivery' => $this->latest_delivery?->toArray(),
        ];
    }
}
