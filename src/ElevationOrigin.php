<?php

declare(strict_types=1);

namespace Endurance\Geo;

enum ElevationOrigin: string
{
    case Reported = 'reported';
    case Derived = 'derived';
    case Unknown = 'unknown';
}
