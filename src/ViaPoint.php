<?php

declare(strict_types=1);

namespace Youmad\Endurance\Geo;

use Youmad\Endurance\Foundation\ValueObject\Coordinate;

/** A place to visit; adjacency does not assert a known connecting path. */
final readonly class ViaPoint
{
    public function __construct(
        public int $index,
        public Coordinate $coordinate,
        public ?Elevation $elevation = null,
    ) {
        if ($index < 0) {
            throw new \InvalidArgumentException('Via point index must be nonnegative.');
        }
    }
}
