<?php

declare(strict_types=1);

namespace Endurance\Geo;

final readonly class PathLengthSummary
{
    public string $algorithm;
    public float $radiusMetres;
    public bool $hasBreaks;

    /** @internal Produced by PathLength; no connected edge means unknown length. */
    public function __construct(
        public int $pointCount,
        public int $partCount,
        public int $edgeCount,
        public ?float $knownLengthMetres,
    ) {
        $this->algorithm = SphericalDistance::ALGORITHM;
        $this->radiusMetres = SphericalDistance::RADIUS_METRES;
        $this->hasBreaks = $partCount > 1;
    }
}
