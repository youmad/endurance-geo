<?php

declare(strict_types=1);

namespace Youmad\Endurance\Geo;

enum ElevationOrigin: string
{
    case Reported = 'reported';
    case Derived = 'derived';
    case Unknown = 'unknown';
}
