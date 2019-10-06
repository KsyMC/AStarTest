<?php

declare(strict_types=1);

namespace k0si\astartest\pathfinding;

use pocketmine\world\World;
use pocketmine\entity\Entity;

class PathFinder {
    /** @var Path */
    private $path;

    /** @var PathPoint[] */
    private $pathOptions = [];

    /** @var NodeProcessor */
    private $nodeProcessor;

    public function __construct(NodeProcessor $nodeProcessor) {
        $this->path = new Path();
        $this->nodeProcessor = $nodeProcessor;
    }

    public function createEntityPathToEntity(World $world, Entity $from, Entity $to, float $distance): ?PathEntity {
        $pos = $to->getPosition();
        return $this->createEntityPathTo($world, $from, $pos->x, $to->getBoundingBox()->minY, $pos->z, $distance);
    }

    public function createEntityPathToPos(World $world, Entity $entity, Vector3 $target, $distance): ?PathEntity {
        return $this->createEntityPathTo($world, $entity, $target->x + 0.5, $target->y + 0.5, $target->z + 0.5, $distance);
    }

    public function createEntityPathTo(World $world, Entity $entity, float $x, float $y, float $z, float $distance): ?PathEntity {
        $this->path->clearPath();
        $this->nodeProcessor->initProcessor($world, $entity);
        $startPoint = $this->nodeProcessor->getPathPointTo($entity);
        $endPoint = $this->nodeProcessor->getPathPointToCoords($entity, $x, $y, $z);
        $pathEntity = $this->addToPath($entity, $startPoint, $endPoint, $distance);
        $this->nodeProcessor->postProcess();
        return $pathEntity;
    }

    public function addToPath(Entity $entity, PathPoint $start, PathPoint $end, float $maxDistance): ?PathEntity {
        $start->totalPathDistance = 0.0;
        $start->distanceToNext = $start->distanceToSquared($end);
        $start->distanceToTarget = $start->distanceToNext;
        $this->path->clearPath();
        $this->path->addPoint($start);
        $pathpoint = $start;

        while (!$this->path->isPathEmpty()) {
            $pathpoint1 = $this->path->dequeue();

            if ($pathpoint1->compareTo($end)) {
                return $this->createEntityPath($start, $end);
            }

            if ($pathpoint1->distanceToSquared($end) < $pathpoint->distanceToSquared($end)) {
                $pathpoint = $pathpoint1;
            }

            $pathpoint1->visited = true;
            $count = $this->nodeProcessor->findPathOptions($this->pathOptions, $entity, $pathpoint1, $end, $maxDistance);

            for ($i = 0; $i < $count; $i++) {
                $pathpoint2 = $this->pathOptions[$i];
                $distance = $pathpoint1->totalPathDistance + $pathpoint1->distanceToSquared($pathpoint2);

                if ($distance < $maxDistance * 2.0 && (!$pathpoint2->isAssigned() || $distance < $pathpoint2->totalPathDistance)) {
                    $pathpoint2->previous = $pathpoint1;
                    $pathpoint2->totalPathDistance = $distance;
                    $pathpoint2->distanceToNext = $pathpoint2->distanceToSquared($end);

                    if ($pathpoint2->isAssigned()) {
                        $this->path->changeDistance($pathpoint2, $pathpoint2->totalPathDistance + $pathpoint2->distanceToNext);
                    } else {
                        $pathpoint2->distanceToTarget = $pathpoint2->totalPathDistance + $pathpoint2->distanceToNext;
                        $this->path->addPoint($pathpoint2);
                    }
                }
            }
        }

        if ($pathpoint === $start) {
            return null;
        } else {
            return $this->createEntityPath($start, $pathpoint);
        }
    }

    private function createEntityPath(PathPoint $start, PathPoint $end): PathEntity {
        $i = 1;

        for ($pathpoint = $end; $pathpoint->previous != null; $pathpoint = $pathpoint->previous) {
            $i++;
        }

        $apathpoint = [];
        $temp = $end;
        $i--;

        for ($apathpoint[$i] = $end; $temp->previous != null; $apathpoint[$i] = $temp) {
            $temp = $temp->previous;
            $i--;
        }
        return new PathEntity($apathpoint);
    }
}