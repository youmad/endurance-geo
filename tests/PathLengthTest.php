<?php

declare(strict_types=1);

namespace Youmad\Endurance\Geo\Tests;

use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Geo\Elevation;
use Youmad\Endurance\Geo\PathGeometry;
use Youmad\Endurance\Geo\PathLength;
use Youmad\Endurance\Geo\PathPoint;
use Youmad\Endurance\Geo\PathPosition;
use Youmad\Endurance\Geo\SphericalDistance;

final class PathLengthTest extends TestCase
{
    public function testNoEdgesMeansUnknownAndRepeatedCoordinatesMeanZero(): void
    {
        $length = new PathLength();
        self::assertNull($length->summary()->knownLengthMetres);
        self::assertSame(0, $length->summary()->pointCount);
        $length->push(self::point(0, 0));
        $snapshot = $length->summary();
        self::assertNull($snapshot->knownLengthMetres);
        $length->push(self::point(1, 0));
        self::assertNull($length->summary()->knownLengthMetres);
        self::assertTrue($length->summary()->hasBreaks);
        $length->push(self::point(1, 1));
        self::assertSame(0.0, $length->summary()->knownLengthMetres);
        self::assertSame(1, $length->summary()->edgeCount);
        self::assertSame(1, $snapshot->pointCount);
        self::assertFalse($snapshot->hasBreaks);
    }

    public function testPagingPreservesContinuityAndNeverBridgesParts(): void
    {
        $pages = [
            [self::point(0, 0), self::point(0, 1, 1.0)],
            [],
            [self::point(0, 2, 2.0), self::point(1, 0, 100.0)],
            [self::point(1, 1, 101.0), self::point(2, 0, -100.0)],
        ];
        $paged = new PathLength();
        foreach ($pages as $page) {
            $paged->append($page);
        }
        $whole = new PathLength();
        $whole->append(new PathGeometry(array_merge(...$pages)));
        self::assertEquals($whole->summary(), $paged->summary());
        $summary = $paged->summary();
        self::assertSame(6, $summary->pointCount);
        self::assertSame(3, $summary->partCount);
        self::assertSame(3, $summary->edgeCount);
        self::assertTrue($summary->hasBreaks);
        self::assertEqualsWithDelta(6_371_008.8 * M_PI / 60, $summary->knownLengthMetres, 1e-8);
        self::assertSame('sphere-great-circle-v1', $summary->algorithm);
        self::assertSame(6_371_008.8, $summary->radiusMetres);
    }

    public function testLengthIgnoresElevationAndDoesNotNeedTime(): void
    {
        $length = new PathLength();
        $length->append([
            new PathPoint(new PathPosition(0, 0), new Coordinate(0.0, 0.0), new Elevation(-100.0)),
            new PathPoint(new PathPosition(0, 1), new Coordinate(0.0, 0.0), new Elevation(8000.0)),
        ]);
        self::assertSame(0.0, $length->summary()->knownLengthMetres);
    }

    public function testBadPageLeavesOnlyAcceptedPrefixAndDoesNotResetIndexes(): void
    {
        $length = new PathLength();
        $length->append([self::point(0, 0)]);
        try {
            $length->append([self::point(0, 1, 1.0), self::point(0, 0), self::point(0, 2, 2.0)]);
            self::fail('Restarted page indexes must fail.');
        } catch (\InvalidArgumentException) {
            self::assertSame(2, $length->summary()->pointCount);
            $length->push(self::point(0, 2, 2.0));
            self::assertSame(3, $length->summary()->pointCount);
        }
    }

    public function testLongSinglePassStreamRetainsBoundedMemory(): void
    {
        $startMemory = memory_get_usage();
        $maxGrowth = 0;
        $count = 200_001;
        $source = static function () use ($startMemory, &$maxGrowth, $count): \Generator {
            for ($i = 0; $i < $count; ++$i) {
                if (0 === $i % 1000) {
                    $maxGrowth = max($maxGrowth, memory_get_usage() - $startMemory);
                }
                yield self::point(0, $i, $i * 0.000001);
            }
        };
        $length = new PathLength();
        $length->append(new PathGeometry($source()));
        self::assertSame($count, $length->summary()->pointCount);
        self::assertLessThan(2 * 1024 * 1024, $maxGrowth);
        self::assertEqualsWithDelta(SphericalDistance::RADIUS_METRES * deg2rad(0.2), $length->summary()->knownLengthMetres, 1e-6);
    }

    private static function point(int $part, int $point, float $longitude = 0.0): PathPoint
    {
        return new PathPoint(new PathPosition($part, $point), new Coordinate(0.0, $longitude));
    }
}
