<?php

declare(strict_types=1);

namespace k0si\astartest\pathfinding;

class PathPoint {
    /** @var int */
    public $x;

    /** @var int */
    public $y;

    /** @var int */
    public $z;

    /** @var int */
    public $hash;

    /** @var int */
    public $index = -1;

    /** @var float */
    public $totalPathDistance = 0.0;

    /** @var float */
    public $distanceToNext = 0.0;

    /** @var float */
    public $distanceToTarget = 0.0;

    /** @var PathPoint|null */
    public $previous = null;

    /** @var bool */
    public $visited = false;

    public function __construct(int $x, int $y, int $z) {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
        $this->hash = PathPoint::makeHash($x, $y, $z);
    }

    public static function makeHash(int $x, int $y, int $z): int {
        return $y & 255 | ($x & 32767) << 8 | ($z & 32767) << 24 | ($x < 0 ? PHP_INT_MIN : 0) | ($z < 0 ? 32768 : 0);
    }

    public function distanceTo(PathPoint $pathpoint): float {
        $x = (float) $pathpoint->x - $this->x;
        $y = (float) $pathpoint->y - $this->y;
        $z = (float) $pathpoint->z - $this->z;
        return sqrt($x * $x + $y * $y + $z * $z);
    }

    public function distanceToSquared(PathPoint $pathpoint): float {
        $x = (float) $pathpoint->x - $this->x;
        $y = (float) $pathpoint->y - $this->y;
        $z = (float) $pathpoint->z - $this->z;
        return $x * $x + $y * $y + $z * $z;
    }

    public function isAssigned(): bool {
        return $this->index >= 0;
    }

    function __toString() {
        return "PathPoint(x:" . $this->x . ", y:" . $this->y . ", z:" . $this->z . ")";
    }

    function compareTo(PathPoint $pathpoint): bool {
        return $this->hash == $pathpoint->hash && $this->x == $pathpoint->x && $this->y == $pathpoint->y && $this->z == $pathpoint->z;
    }
}