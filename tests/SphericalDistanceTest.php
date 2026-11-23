<?php

declare(strict_types=1);

namespace Youmad\Endurance\Geo\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Youmad\Endurance\Foundation\ValueObject\Coordinate;
use Youmad\Endurance\Geo\SphericalDistance;

final class SphericalDistanceTest extends TestCase
{
    #[DataProvider('arcs')]
    public function testAnalyticSphericalArcs(float $lat1, float $lon1, float $lat2, float $lon2, float $degrees): void
    {
        $from = new Coordinate($lat1, $lon1);
        $to = new Coordinate($lat2, $lon2);
        $expected = 6_371_008.8 * deg2rad($degrees);
        self::assertEqualsWithDelta($expected, SphericalDistance::metres($from, $to), 1e-7);
        self::assertEqualsWithDelta($expected, SphericalDistance::metres($to, $from), 1e-7);
    }

    /** @return iterable<string, array{float, float, float, float, float}> */
    public static function arcs(): iterable
    {
        yield 'coincident' => [50.0, 65.0, 50.0, 65.0, 0.0];
        yield 'same meridian' => [-10.0, 65.0, 30.0, 65.0, 40.0];
        yield 'antimeridian short arc' => [0.0, 179.9, 0.0, -179.9, 0.2];
        yield 'antimeridian aliases' => [40.0, -180.0, 40.0, 180.0, 0.0];
        yield 'north pole aliases' => [90.0, -100.0, 90.0, 50.0, 0.0];
        yield 'south pole aliases' => [-90.0, -180.0, -90.0, 180.0, 0.0];
        yield 'opposite poles' => [-90.0, -80.0, 90.0, 100.0, 180.0];
        yield 'over north pole' => [89.0, 0.0, 89.0, 180.0, 2.0];
        yield 'near pole' => [89.999999, 0.0, 89.999999, 180.0, 0.000002];
        yield 'antipodal' => [30.0, 0.0, -30.0, 180.0, 180.0];
        yield 'near antipodal' => [0.0, 0.0, 0.0, 179.999999, 179.999999];
        yield 'submillimetre' => [0.0, 0.0, 0.0, 0.000000001, 0.000000001];
        yield 'oblique arc' => [0.0, 0.0, 30.0, 60.0, 64.34109372674472];
        yield 'quarter circle' => [90.0, 71.0, 0.0, -20.0, 90.0];
    }

    #[DataProvider('invalidCoordinates')]
    public function testUsesFoundationCoordinateValidation(float $latitude, float $longitude): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Coordinate($latitude, $longitude);
    }

    /** @return iterable<array{float, float}> */
    public static function invalidCoordinates(): iterable
    {
        yield [90.00001, 0.0];
        yield [-90.00001, 0.0];
        yield [0.0, 180.00001];
        yield [0.0, -180.00001];
        yield [NAN, 0.0];
        yield [0.0, NAN];
        yield [INF, 0.0];
        yield [0.0, -INF];
    }
}
