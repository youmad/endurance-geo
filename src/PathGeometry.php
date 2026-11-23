<?php

declare(strict_types=1);

namespace Youmad\Endurance\Geo;

/**
 * Validation requires exhaustion; generator-backed input is single-pass and unbuffered.
 *
 * @implements \IteratorAggregate<int, PathPoint>
 */
final readonly class PathGeometry implements \IteratorAggregate
{
    /** @param iterable<mixed> $points Values are validated lazily as PathPoint. */
    public function __construct(private iterable $points)
    {
    }

    /** @return \Generator<int, PathPoint> */
    public function getIterator(): \Generator
    {
        $sequence = new PathSequence();
        foreach ($this->points as $point) {
            if (!$point instanceof PathPoint) {
                throw new \InvalidArgumentException('Path geometry accepts only PathPoint values.');
            }
            $sequence->accept($point->position);
            yield $point;
        }
    }
}
