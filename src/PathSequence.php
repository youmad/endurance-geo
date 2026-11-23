<?php

declare(strict_types=1);

namespace Endurance\Geo;

/** Keep one instance across pages of the same path. */
final class PathSequence
{
    private ?PathPosition $previous = null;

    /** True starts a new part; rejection leaves the sequence state unchanged. */
    public function accept(PathPosition $position): bool
    {
        if (null === $this->previous) {
            if (0 !== $position->partIndex || 0 !== $position->pointIndex) {
                throw new \InvalidArgumentException('A complete path must start at part 0, point 0.');
            }
            $startsPart = true;
        } elseif ($position->partIndex === $this->previous->partIndex) {
            if (PHP_INT_MAX === $this->previous->pointIndex || $position->pointIndex !== $this->previous->pointIndex + 1) {
                throw new \InvalidArgumentException('Point indexes must be consecutive within a part.');
            }
            $startsPart = false;
        } else {
            if (PHP_INT_MAX === $this->previous->partIndex || $position->partIndex !== $this->previous->partIndex + 1 || 0 !== $position->pointIndex) {
                throw new \InvalidArgumentException('The next part must be consecutive and start at point 0.');
            }
            $startsPart = true;
        }
        $this->previous = $position;

        return $startsPart;
    }
}
