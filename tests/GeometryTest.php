<?php

declare(strict_types=1);

namespace Youmad\Endurance\Geo\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Geo\Elevation;
use Youmad\Endurance\Geo\ElevationOrigin;
use Youmad\Endurance\Geo\PathGeometry;
use Youmad\Endurance\Geo\PathLength;
use Youmad\Endurance\Geo\PathPoint;
use Youmad\Endurance\Geo\PathPosition;
use Youmad\Endurance\Geo\PathSequence;
use Youmad\Endurance\Geo\ViaPoint;
use Youmad\Endurance\Geo\ViaPoints;

final class GeometryTest extends TestCase
{
    public function testGeometryIsLazyAndPreservesOrderDuplicatesAndElevation(): void
    {
        $elevation = new Elevation(-12.5, ElevationOrigin::Reported, 'device/barometer', 'EGM96');
        $first = new PathPoint(new PathPosition(0, 0), new Coordinate(1.0, 2.0), $elevation);
        $second = new PathPoint(new PathPosition(0, 1), $first->coordinate);
        $third = new PathPoint(new PathPosition(1, 0), $first->coordinate, new Elevation(0.0));
        $reads = 0;
        $source = static function () use (&$reads, $first, $second, $third): \Generator {
            foreach ([$first, $second, $third] as $point) {
                ++$reads;
                yield 'same-source-key' => $point;
            }
        };
        $geometry = new PathGeometry($source());
        self::assertSame(0, $reads);
        self::assertSame([$first, $second, $third], iterator_to_array($geometry));
        self::assertSame(3, $reads);
        self::assertSame($elevation, $first->elevation);
        self::assertSame(-12.5, $elevation->metres);
        self::assertSame('EGM96', $elevation->verticalDatum);
        self::assertSame('device/barometer', $elevation->source);
        self::assertSame(ElevationOrigin::Reported, $elevation->origin);
        self::assertNull($second->elevation);
        self::assertSame(0.0, $third->elevation?->metres);
        self::assertSame(ElevationOrigin::Unknown, $third->elevation->origin);
    }

    public function testEmptyGeometryAndViaPointsAreValidStreams(): void
    {
        self::assertSame([], iterator_to_array(new PathGeometry([])));
        self::assertSame([], iterator_to_array(new ViaPoints([])));
    }

    public function testGeometryRejectsViaPointAtConsumption(): void
    {
        $geometry = new PathGeometry([new ViaPoint(0, new Coordinate(0.0, 0.0))]);
        $this->expectException(\InvalidArgumentException::class);
        iterator_to_array($geometry);
    }

    public function testViaPointsRejectGeometryPointAtConsumption(): void
    {
        $points = new ViaPoints([new PathPoint(new PathPosition(0, 0), new Coordinate(0.0, 0.0))]);
        $this->expectException(\InvalidArgumentException::class);
        iterator_to_array($points);
    }

    public function testValidationIsDeferredUntilTheBadPointIsRead(): void
    {
        $geometry = new PathGeometry([
            new PathPoint(new PathPosition(0, 0), new Coordinate(0.0, 0.0)),
            new PathPoint(new PathPosition(0, 2), new Coordinate(0.0, 1.0)),
        ]);
        $iterator = $geometry->getIterator();
        $iterator->rewind();
        self::assertSame(0, $iterator->current()->position->pointIndex);
        $this->expectException(\InvalidArgumentException::class);
        $iterator->next();
    }

    /** @param list<array{int, int}> $positions */
    #[DataProvider('invalidSequences')]
    public function testRejectsInvalidSequence(array $positions): void
    {
        $sequence = new PathSequence();
        $this->expectException(\InvalidArgumentException::class);
        foreach ($positions as [$part, $point]) {
            $sequence->accept(new PathPosition($part, $point));
        }
    }

    /** @return iterable<string, array{list<array{int, int}>}> */
    public static function invalidSequences(): iterable
    {
        yield 'negative part' => [[[-1, 0]]];
        yield 'negative point' => [[[0, -1]]];
        yield 'nonzero first part' => [[[1, 0]]];
        yield 'nonzero first point' => [[[0, 1]]];
        yield 'duplicate address' => [[[0, 0], [0, 0]]];
        yield 'point gap' => [[[0, 0], [0, 2]]];
        yield 'part gap' => [[[0, 0], [2, 0]]];
        yield 'new part wrong start' => [[[0, 0], [1, 1]]];
        yield 'return to old part' => [[[0, 0], [1, 0], [0, 1]]];
        yield 'reverse point order' => [[[0, 0], [0, 1], [0, 0]]];
    }

    public function testInvalidPositionDoesNotAdvanceSequence(): void
    {
        $sequence = new PathSequence();
        self::assertTrue($sequence->accept(new PathPosition(0, 0)));
        try {
            $sequence->accept(new PathPosition(1, 1));
            self::fail('Invalid next part must fail.');
        } catch (\InvalidArgumentException) {
            self::assertFalse($sequence->accept(new PathPosition(0, 1)));
            self::assertTrue($sequence->accept(new PathPosition(1, 0)));
        }
    }

    public function testViaPointsPreserveRepeatedPlacesWithoutAssertingEdges(): void
    {
        $coordinate = new Coordinate(50.0, 65.0);
        $elevation = new Elevation(100.0, ElevationOrigin::Derived, 'dem/v2');
        $points = [new ViaPoint(0, $coordinate), new ViaPoint(1, $coordinate, $elevation)];
        self::assertSame($points, iterator_to_array(new ViaPoints($points)));
        self::assertSame($elevation, $points[1]->elevation);
        $this->expectException(\TypeError::class);

        /* @phpstan-ignore argument.type */
        (new PathLength())->append(new ViaPoints($points));
    }

    /** @param list<int> $indexes */
    #[DataProvider('invalidViaIndexes')]
    public function testRejectsInvalidViaIndexes(array $indexes): void
    {
        $this->expectException(\InvalidArgumentException::class);
        iterator_to_array(new ViaPoints(array_map(
            static fn (int $index): ViaPoint => new ViaPoint($index, new Coordinate(0.0, 0.0)),
            $indexes,
        )));
    }

    /** @return iterable<array{list<int>}> */
    public static function invalidViaIndexes(): iterable
    {
        yield [[-1]];
        yield [[1]];
        yield [[0, 0]];
        yield [[0, 2]];
        yield [[0, 1, 0]];
    }

    #[DataProvider('invalidElevations')]
    public function testElevationRejectsNonFiniteValues(float $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Elevation($value);
    }

    /** @return iterable<array{float}> */
    public static function invalidElevations(): iterable
    {
        yield [NAN];
        yield [INF];
        yield [-INF];
    }

    public function testUnknownElevationMetadataUsesNull(): void
    {
        $elevation = new Elevation(0.0);
        self::assertNull($elevation->source);
        self::assertNull($elevation->verticalDatum);
        $this->expectException(\InvalidArgumentException::class);
        new Elevation(0.0, source: '  ');
    }

    public function testEmptyVerticalDatumIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Elevation(0.0, verticalDatum: '');
    }
}
