# Endurance Geo

Streaming path geometry, ordered via points and horizontal distance for PHP 8.5.

```bash
composer require youmad/endurance-geo
```

```php
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Geo\{PathGeometry, PathLength, PathPoint, PathPosition};

$path = new PathGeometry([
    new PathPoint(new PathPosition(0, 0), new Coordinate(0.0, 179.0)),
    new PathPoint(new PathPosition(0, 1), new Coordinate(0.0, -179.0)),
]);
$length = new PathLength();
$length->append($path);
$summary = $length->summary();
```

- Part and point indexes are consecutive and zero-based. Parts preserve gaps;
  repeated coordinates are valid. Adjacent parts never acquire a connecting edge.
- Geometry is lazy and generator-backed input is single-pass. Consume it fully
  for validation. Keep the same `PathLength`/`PathSequence` across successive
  pages; an accepted prefix after failure is not a complete result.
- `ViaPoints` describes places to visit without asserting a connecting path.
  Missing elevation stays null; zero and negative elevation remain known values.
- Length uses a sphere of radius 6,371,008.8 m and ignores elevation. No edges
  means unknown length; observed zero-length edges mean a known zero.

[Executable example](examples/measure-path.php).

License: [MPL-2.0](LICENSE).
