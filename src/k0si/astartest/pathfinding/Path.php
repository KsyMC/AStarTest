<?php

declare(strict_types=1);

namespace k0si\astartest\pathfinding;

class Path {

    /** @var PathPoint[] */
    private $pathPoints = [];

    /** @var int */
    private $count = 0;

    public function addPoint(PathPoint $point): ?PathPoint {
        if ($point->index < 0) {
            $point->index = $this->count;
            $this->pathPoints[$this->count] = $point;
            $this->sortBack($this->count++);

            return $point;
        }
        return null;
    }

    public function clearPath(): void {
        $this->count = 0;
        $this->pathPoints = [];
    }

    public function dequeue(): PathPoint {
        $pathpoint = array_shift($this->pathPoints);
        array_unshift($this->pathPoints, array_pop($this->pathPoints));
        $this->count--;

        if ($this->count > 0) {
            $this->sortForward(0);
        }

        $pathpoint->index = -1;
        return $pathpoint;
    }

    public function changeDistance(PathPoint $pathpoint, float $distance): void {
        $old = $pathpoint->distanceToTarget;
        $pathpoint->distanceToTarget = $distance;

        if ($distance < $old) {
            $this->sortBack($pathpoint->index);
        } else {
            $this->sortForward($pathpoint->index);
        }
    }

    public function sortBack(int $index): void {
        $pathpoint = $this->pathPoints[$index];

        $i = 0;
        for ($f = $pathpoint->distanceToTarget; $index > 0; $index = $i) {
            $i = $index - 1 >> 1;
            $pathpoint1 = $this->pathPoints[$i];

            if ($f >= $pathpoint1->distanceToTarget) {
                break;
            }
            $pathpoint1->index = $index;
            $this->pathPoints[$index] = $pathpoint1;
        }

        $pathpoint->index = $index;
        $this->pathPoints[$index] = $pathpoint;
    }

    public function sortForward(int $index): void {
        $pathpoint = $this->pathPoints[$index];
        $f = $pathpoint->distanceToTarget;

        while (true) {
            $i = 1 + ($index << 1);
            $j = $i + 1;

            if ($i >= $this->count) {
                break;
            }

            $pathpoint1 = $this->pathPoints[$i];
            $f1 = $pathpoint1->distanceToTarget;
            $pathpoint2 = null;

            $f2 = 0.0;

            if ($j >= $this->count) {
                $pathpoint2 = null;
                $f2 = PHP_FLOAT_MAX;
            } else {
                $pathpoint2 = $this->pathPoints[$j];
                $f2 = $pathpoint2->distanceToTarget;
            }

            if ($f1 < $f2) {
                if ($f1 >= $f) {
                    break;
                }

                $pathpoint1->index = $index;
                $this->pathPoints[$index] = $pathpoint1;
                $index = $i;
            } else {
                if ($f2 >= $f) {
                    break;
                }

                $pathpoint2->index = $index;
                $this->pathPoints[$index] = $pathpoint2;
                $index = $j;
            }
        }

        $pathpoint->index = $index;
        $this->pathPoints[$index] = $pathpoint;
    }

    public function isPathEmpty(): bool {
        return $this->count == 0;
    }
}