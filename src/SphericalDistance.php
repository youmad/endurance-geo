<?php

declare(strict_types=1);

namespace Endurance\Geo;

use Endurance\Foundation\ValueObject\Coordinate;

final class SphericalDistance
{
    public const string ALGORITHM = 'sphere-great-circle-v1';
    public const float RADIUS_METRES = 6_371_008.8;

    public static function metres(Coordinate $from, Coordinate $to): float
    {
        $longitude = fmod($to->longitude - $from->longitude, 360.0);
        if ($longitude > 180.0) {
            $longitude -= 360.0;
        } elseif ($longitude < -180.0) {
            $longitude += 360.0;
        }
        if ($from->latitude === $to->latitude && (0.0 === $longitude || 90.0 === abs($from->latitude))) {
            return 0.0;
        }

        $latitude1 = deg2rad($from->latitude);
        $latitude2 = deg2rad($to->latitude);
        $delta = deg2rad($longitude);
        // Exact poles have no meaningful longitude. Avoid residual cos(pi / 2).
        $cos1 = 90.0 === abs($from->latitude) ? 0.0 : cos($latitude1);
        $cos2 = 90.0 === abs($to->latitude) ? 0.0 : cos($latitude2);
        $sin1 = sin($latitude1);
        $sin2 = sin($latitude2);
        $cross = hypot($cos2 * sin($delta), $cos1 * $sin2 - $sin1 * $cos2 * cos($delta));
        $dot = $sin1 * $sin2 + $cos1 * $cos2 * cos($delta);

        return self::RADIUS_METRES * atan2($cross, $dot);
    }
}
