<?php

declare(strict_types=1);

use Endurance\Foundation\ValueObject\Coordinate;
use Endurance\Geo\PathGeometry;
use Endurance\Geo\PathLength;
use Endurance\Geo\PathPoint;
use Endurance\Geo\PathPosition;

require $argv[1] ?? dirname(__DIR__).'/vendor/autoload.php';

$geometry = new PathGeometry((static function (): Generator {
    yield new PathPoint(new PathPosition(0, 0), new Coordinate(0.0, 179.0));
    yield new PathPoint(new PathPosition(0, 1), new Coordinate(0.0, -179.0));

    yield new PathPoint(new PathPosition(1, 0), new Coordinate(50.0, 65.0));
})());
$length = new PathLength();
$length->append($geometry);
$summary = $length->summary();
if (3 !== $summary->pointCount || 2 !== $summary->partCount || 1 !== $summary->edgeCount
    || !$summary->hasBreaks || null === $summary->knownLengthMetres
    || abs($summary->knownLengthMetres - 222_390.1604670658) > 0.000001) {
    throw new RuntimeException('Independent Geo consumer produced an unexpected result.');
}
printf("%d points, %d parts, %.3f known metres (%s)\n", $summary->pointCount, $summary->partCount, $summary->knownLengthMetres, $summary->algorithm);
