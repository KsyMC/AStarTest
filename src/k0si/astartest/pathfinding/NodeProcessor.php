<?php

declare(strict_types=1);

namespace k0si\astartest\pathfinding;

use pocketmine\world\World;
use pocketmine\entity\Entity;

abstract class NodeProcessor {

    /** @var World */
    protected $world;

    /** @var PathPoint[] */
    protected $pointMap = [];

    /** @var int */
    protected $entitySizeX;

    /** @var int */
    protected $entitySizeY;

    /** @var int */
    protected $entitySizeZ;

    public function initProcessor(World $world, Entity $entity): void {
        $this->world = $world;
        $this->pointMap = [];
        $this->entitySizeX = (int) floor($entity->width + 1.0);
        $this->entitySizeY = (int) floor($entity->height + 1.0);
        $this->entitySizeZ = (int) floor($entity->width + 1.0);
    }

    public function postProcess(): void {}

    protected function openPoint(int $x, int $y, int $z): PathPoint {
        $hash = PathPoint::makeHash($x, $y, $z);
        $pathpoint = $this->pointMap[$hash] ?? null;

        if ($pathpoint === null) {
            $pathpoint = new PathPoint($x, $y, $z);
            $this->pointMap[$hash] = $pathpoint;
        }
        return $pathpoint;
    }

    public abstract function getPathPointTo(Entity $entity): PathPoint;

    public abstract function getPathPointToCoords(Entity $entity, float $x, float $y, float $z): PathPoint;

    public abstract function findPathOptions(array &$pathOptions, Entity $entity, PathPoint $currentPoint, PathPoint $targetPoint, float $maxDistance): int;
}