<?php

declare(strict_types=1);

namespace Tripwire\Server\Api;

use Generator;
use Tripwire\Server\Http\HttpClient;
use Tripwire\Server\Resource\SessionDetail;
use Tripwire\Server\Resource\SessionSummary;
use Tripwire\Server\Result\ListResult;

final class SessionsApi
{
    public function __construct(private readonly HttpClient $http) {}

    public function list(
        ?int $limit = null,
        ?string $cursor = null,
        ?string $verdict = null,
        ?string $search = null,
    ): ListResult {
        $response = $this->http->requestJson('GET', '/v1/sessions', [
            'limit' => $limit,
            'cursor' => $cursor,
            'verdict' => $verdict,
            'search' => $search,
        ]);

        $items = [];
        foreach ((array) $response['data'] as $item) {
            $items[] = SessionSummary::fromArray((array) $item);
        }

        $pagination = (array) ($response['pagination'] ?? []);

        return new ListResult(
            $items,
            (int) $pagination['limit'],
            (bool) $pagination['hasMore'],
            isset($pagination['nextCursor']) ? (string) $pagination['nextCursor'] : null,
        );
    }

    public function get(string $sessionId): SessionDetail
    {
        $response = $this->http->requestJson('GET', '/v1/sessions/' . rawurlencode($sessionId));
        return SessionDetail::fromArray((array) $response['data']);
    }

    /**
     * @return Generator<int, SessionSummary>
     */
    public function iterate(
        ?int $limit = null,
        ?string $verdict = null,
        ?string $search = null,
    ): Generator {
        $cursor = null;

        do {
            $page = $this->list($limit, $cursor, $verdict, $search);
            foreach ($page->items as $item) {
                yield $item;
            }
            $cursor = $page->hasMore ? $page->nextCursor : null;
        } while ($cursor !== null);
    }
}

