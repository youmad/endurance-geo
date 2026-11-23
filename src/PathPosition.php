<?php

declare(strict_types=1);

namespace Endurance\Geo;

final readonly class PathPosition
{
    public function __construct(public int $partIndex, public int $pointIndex)
    {
        if ($partIndex < 0 || $pointIndex < 0) {
            throw new \InvalidArgumentException('Path indexes must be nonnegative.');
        }
    }
}
