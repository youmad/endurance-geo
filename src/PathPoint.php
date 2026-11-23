<?php

declare(strict_types=1);

namespace Youmad\Endurance\Geo;

use Youmad\Endurance\Foundation\ValueObject\Coordinate;

/** Adjacent points in the same part describe a continuous geometric edge. */
final readonly class PathPoint
{
    public function __construct(
        public PathPosition $position,
        public Coordinate $coordinate,
        public ?Elevation $elevation = null,
    ) {
    }
}
