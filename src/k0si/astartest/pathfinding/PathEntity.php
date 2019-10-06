<?php

declare(strict_types=1);

namespace k0si\astartest\pathfinding;

use pocketmine\math\Vector3;
use pocketmine\entity\Entity;

class PathEntity {

    /** @var PathPoint[] */
    private $points = [];

    /** @var int */
    private $currentPathIndex = 0;

    /** @var int */
    private $pathLength;

    /**
     * @param array $pathPoints PathPoint[]
     */
    public function __construct(array $pathpoints) {
        $this->points = $pathpoints;
        $this->pathLength = count($pathpoints);
    }

    public function incrementPathIndex(): void {
        $this->currentPathIndex++;
    }

    public function isFinished(): bool {
        return $this->currentPathIndex >= $this->pathLength;
    }

    public function getFinalPathPoint(): ?PathPoint {
        return $this->pathLength > 0 ? $this->points[$this->pathLength - 1] : null;
    }

    public function getPathPointFromIndex(int $index): PathPoint {
        return $this->points[$index];
    }

    public function getCurrentPathLength(): int {
        return $this->pathLength;
    }

    public function setCurrentPathLength(int $length): void {
        $this->pathLength = $length;
    }

    public function getCurrentPathIndex(): int {
        return $this->currentPathIndex;
    }

    public function setCurrentPathIndex(int $currentPathIndex): void {
        $this->currentPathIndex = $currentPathIndex;
    }

    public function getVectorFromIndex(Entity $entity, int $index): Vector3 {
        $x = $this->points[$index]->x + (float) ((int) ($entity->width + 1.0)) * 0.5;
        $y = $this->points[$index]->y;
        $z = $this->points[$index]->z + (float) ((int) ($entity->width + 1.0)) * 0.5;
        return new Vector3($x, $y, $z);
    }

    public function getPosition(Entity $entity): Vector3 {
        return $this->getVectorFromIndex($entity, $this->currentPathIndex);
    }

    public function isSamePath(?PathEntity $pathentity): bool {
        if ($pathentity === null) {
            return false;
        } else if (count($pathentity->points) !== count($this->points)) {
            return false;
        } else {
            foreach ($this->points as $i => $point) {
                if ($point->x != $pathentity->points[$i]->x || $point->y != $pathentity->points[$i]->y || $point->z != $pathentity->points[$i]->z) {
                    return false;
                }
            }
            return true;
        }
    }

    public function isDestinationSame(Vector3 $pos): bool {
        $pathpoint = $this->getFinalPathPoint();
        return $pathpoint === null ? false : $pathpoint->x == (int) $pos->x && $pathpoint->z == (int) $pos->z;
    }
}