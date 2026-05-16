<?php

declare(strict_types=1);

namespace Foil\Server\Api;

use Generator;
use Foil\Server\Http\HttpClient;
use Foil\Server\Resource\FingerprintDetail;
use Foil\Server\Resource\FingerprintSummary;
use Foil\Server\Result\ListResult;

final class FingerprintsApi
{
    public function __construct(private readonly HttpClient $http) {}

    public function list(
        ?int $limit = null,
        ?string $cursor = null,
        ?string $search = null,
        ?string $sort = null,
    ): ListResult {
        $response = $this->http->requestJson('GET', '/v1/fingerprints', [
            'limit' => $limit,
            'cursor' => $cursor,
            'search' => $search,
            'sort' => $sort,
        ]);

        $items = [];
        foreach ((array) $response['data'] as $item) {
            $items[] = FingerprintSummary::fromArray((array) $item);
        }

        $pagination = (array) ($response['pagination'] ?? []);

        return new ListResult(
            $items,
            (int) $pagination['limit'],
            (bool) $pagination['has_more'],
            isset($pagination['next_cursor']) ? (string) $pagination['next_cursor'] : null,
        );
    }

    public function get(string $visitorId): FingerprintDetail
    {
        $response = $this->http->requestJson('GET', '/v1/fingerprints/' . rawurlencode($visitorId));
        return FingerprintDetail::fromArray((array) $response['data']);
    }

    /**
     * @return Generator<int, FingerprintSummary>
     */
    public function iterate(
        ?int $limit = null,
        ?string $search = null,
        ?string $sort = null,
    ): Generator {
        $cursor = null;

        do {
            $page = $this->list($limit, $cursor, $search, $sort);
            foreach ($page->items as $item) {
                yield $item;
            }
            $cursor = $page->has_more ? $page->next_cursor : null;
        } while ($cursor !== null);
    }
}
