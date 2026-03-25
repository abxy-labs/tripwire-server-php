<?php

declare(strict_types=1);

namespace Tripwire\Server\Tests\Support;

final class FixtureLoader
{
    /**
     * @return array<string, mixed>
     */
    public static function load(string $relativePath): array
    {
        $absolutePath = dirname(__DIR__, 2) . '/spec/fixtures/' . $relativePath;
        $contents = file_get_contents($absolutePath);
        if ($contents === false) {
            throw new \RuntimeException('Unable to load fixture: ' . $relativePath);
        }

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }
}

