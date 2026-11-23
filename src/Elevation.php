<?php

declare(strict_types=1);

namespace Endurance\Geo;

final readonly class Elevation
{
    public function __construct(
        public float $metres,
        public ElevationOrigin $origin = ElevationOrigin::Unknown,
        public ?string $source = null,
        public ?string $verticalDatum = null,
    ) {
        if (!is_finite($metres)) {
            throw new \InvalidArgumentException('Elevation must be finite.');
        }
        foreach (['source' => $source, 'verticalDatum' => $verticalDatum] as $field => $value) {
            if (null !== $value && '' === trim($value)) {
                throw new \InvalidArgumentException(sprintf('Unknown elevation %s must be null, not an empty string.', $field));
            }
        }
    }
}
