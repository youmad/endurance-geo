<?php

declare(strict_types=1);

namespace Endurance\Geo;

/** @implements \IteratorAggregate<int, ViaPoint> */
final readonly class ViaPoints implements \IteratorAggregate
{
    /** @param iterable<mixed> $points Values are validated lazily as ViaPoint. */
    public function __construct(private iterable $points)
    {
    }

    /** @return \Generator<int, ViaPoint> */
    public function getIterator(): \Generator
    {
        $previous = null;
        foreach ($this->points as $point) {
            if (!$point instanceof ViaPoint) {
                throw new \InvalidArgumentException('Via points accept only ViaPoint values.');
            }
            if (PHP_INT_MAX === $previous || $point->index !== (null === $previous ? 0 : $previous + 1)) {
                throw new \InvalidArgumentException('Via point indexes must be consecutive, starting at 0.');
            }
            $previous = $point->index;
            yield $point;
        }
    }
}
