<?php

declare(strict_types=1);

namespace Endurance\Geo;

/** Constant-memory measurement; retain the same instance between successive pages. */
final class PathLength
{
    private readonly PathSequence $sequence;
    private ?PathPoint $previous = null;
    private int $points = 0;
    private int $parts = 0;
    private int $edges = 0;
    private float $length = 0.0;
    private float $compensation = 0.0;

    public function __construct()
    {
        $this->sequence = new PathSequence();
    }

    public function push(PathPoint $point): void
    {
        if (PHP_INT_MAX === $this->points) {
            throw new \OverflowException('Path point count exceeds the supported range.');
        }
        $startsPart = $this->sequence->accept($point->position);
        if ($startsPart) {
            ++$this->parts;
        } elseif (null !== $this->previous) {
            $distance = SphericalDistance::metres($this->previous->coordinate, $point->coordinate);
            // Compensated summation limits loss of short edges in long paths.
            $adjusted = $distance - $this->compensation;
            $sum = $this->length + $adjusted;
            $this->compensation = ($sum - $this->length) - $adjusted;
            $this->length = $sum;
            ++$this->edges;
        }
        ++$this->points;
        $this->previous = $point;
    }

    /**
     * Failure retains the accepted prefix; it must not be published as complete.
     *
     * @param iterable<PathPoint> $points
     */
    public function append(iterable $points): void
    {
        foreach ($points as $point) {
            $this->push($point);
        }
    }

    public function summary(): PathLengthSummary
    {
        return new PathLengthSummary($this->points, $this->parts, $this->edges, $this->edges > 0 ? $this->length : null);
    }
}
