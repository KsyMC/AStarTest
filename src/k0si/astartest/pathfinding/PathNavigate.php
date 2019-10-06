<?php

declare(strict_types=1);

namespace k0si\astartest\pathfinding;

use pocketmine\math\Vector3;
use pocketmine\math\AxisAlignedBB;
use pocketmine\world\World;
use pocketmine\entity\Entity;
use k0si\astartest\entity\PathFindingLiving;

abstract class PathNavigate {

    /** @var PathFindingLiving|null */
    protected $theEntity;

    /** @var World|null */
    protected $world;

    /** @var PathEntity|null */
    protected $currentPath;

    /** @var float */
    protected $speed = 0.0;

    /** @var int */
    private $totalTicks;

    /** @var int */
    private $ticksAtLastPos;

    /** @var Vector3 */
    private $lastPosCheck;

    /** @var float */
    private $heightRequirement = 1.0;

    /** @var PathFinder */
    private $pathFinder;

    public function __construct(PathFindingLiving $entity, World $world) {
        $this->theEntity = $entity;
        $this->world = $world;
        $this->lastPosCheck = new Vector3();
        $this->pathFinder = $this->getPathFinder();
    }

    protected abstract function getPathFinder(): PathFinder;

    public function getPathSearchRange(): float {
        return 35.0; // Zombie
    }

    public function getPathToXYZ(float $x, float $y, float $z) {
        return $this->getPathToPos(new Vector3((int) floor($x), (int) floor($y), (int) floor($z)));
    }

    public function getPathToPos(Vector3 $pos): ?PathEntity {
        if (!$this->canNavigate()) {
            return null;
        } else {
            $range = $this->getPathSearchRange();
            $pathEntity = $this->pathFinder->createEntityPathToPos($this->world, $this->theEntity, $pos, $range);
            return $pathEntity;
        }
    }

    public function tryMoveToXYZ(float $x, float $y, float $z, float $speed): bool {
        $pathentity = $this->getPathToXYZ(new Vector3(floor($x), (float)((int)$y), floor($z)));
        return $this->setPath($pathentity, $speed);
    }

    public function getPathToEntityLiving(Entity $entity): ?PathEntity {
        if (!$this->canNavigate()) {
            return null;
        } else {
            $range = $this->getPathSearchRange();
            $pathEntity = $this->pathFinder->createEntityPathToEntity($this->world, $this->theEntity, $entity, $range);
            return $pathEntity;
        }
        return null;
    }

    public function tryMoveToEntityLiving(Entity $entity, float $speed): bool {
        $pathentity = $this->getPathToEntityLiving($entity);
        return $entity !== null ? $this->setPath($pathentity, $speed) : false;
    }

    public function setPath(?PathEntity $pathentity, float $speed): bool {
        if ($pathentity === null) {
            $this->currentPath = null;
            return false;
        } else {
            if (!$pathentity->isSamePath($this->currentPath)) {
                $this->currentPath = $pathentity;
            }

            if ($this->currentPath->getCurrentPathLength() == 0) {
                return false;
            } else {
                $this->speed = $speed;
                $pos = $this->getEntityPosition();
                $this->ticksAtLastPos = $this->totalTicks;
                $this->lastPosCheck = $pos;
                return true;
            }
        }
    }

    public function getPath(): PathEntity {
        return $this->currentPath;
    }

    public function onUpdateNavigation(): void {
        $this->totalTicks++;

        if (!$this->noPath()) {
            if ($this->canNavigate()) {
                $this->pathFollow();
            } else if ($this->currentPath !== null && $this->currentPath->getCurrentPathIndex() < $this->currentPath->getCurrentPathLength()) {
                $pos = $this->getEntityPosition();
                $pathPos = $this->currentPath->getVectorFromIndex($this->theEntity, $this->currentPath->getCurrentPathIndex());

                if ($pos->y > $pathPos->y && !$this->theEntity->onGround && floor($pos->x) == floor($pathPos->x) && floor($pos->z) == floor($pathPos->z)) {
                    $this->currentPath->setCurrentPathIndex($this->currentPath->getCurrentPathIndex() + 1);
                }
            }

            if (!$this->noPath()) {
                $pos = $this->currentPath->getPosition($this->theEntity);
                if ($pos !== null) {
                    $aabb = (new AxisAlignedBB($pos->x, $pos->y, $pos->z, $pos->x, $pos->y, $pos->z))->expand(0.5, 0.5, 0.5);
                    $list = $this->world->getCollisionBoxes($this->theEntity, $aabb->addCoord(0.0, -1.0, 0.0));
                    $y = -1.0;
                    $aabb->offset(0.0, 1.0, 0.0);

                    foreach ($list as $value) {
                        $y = $value->calculateYOffset($aabb, $y);
                    }

                    $this->theEntity->moveX = $pos->x;
                    $this->theEntity->moveY = $pos->y + $y;
                    $this->theEntity->moveZ = $pos->z;
                    $this->theEntity->moveSpeed = $this->speed;
                    $this->theEntity->moveUpdate = true;
                }
            }
        }
    }

    protected function pathFollow(): void {
        $pos = $this->getEntityPosition();
        $length = $this->currentPath->getCurrentPathLength();

        for ($index = $this->currentPath->getCurrentPathIndex(); $index < $this->currentPath->getCurrentPathLength(); $index++) {
            if ($this->currentPath->getPathPointFromIndex($index)->y != (int) floor($pos->y)) {
                $length = $index;
                break;
            }
        }

        $size = $this->theEntity->width * $this->theEntity->width * $this->heightRequirement;

        for ($i = $this->currentPath->getCurrentPathIndex(); $i < $length; $i++) {
            $pathPos = $this->currentPath->getVectorFromIndex($this->theEntity, $i);
            if ($pos->distanceSquared($pathPos) < $size) {
                $this->currentPath->setCurrentPathIndex($i + 1);
            }
        }

        $width = (int) ceil($this->theEntity->width);
        $height = (int) $this->theEntity->height + 1;

        for ($i = $length - 1; $i >= $this->currentPath->getCurrentPathIndex(); $i--) {
            if ($this->isDirectPathBetweenPoints($pos, $this->currentPath->getVectorFromIndex($this->theEntity, $i), $width, $height, $width)) {
                $this->currentPath->setCurrentPathIndex($i);
                break;
            }
        }

        $this->checkForStuck($pos);
    }

    protected function checkForStuck(Vector3 $pos): void {
        if ($this->totalTicks - $this->ticksAtLastPos > 100) {
            if ($pos->distanceSquared($this->lastPosCheck) < 2.25) {
                $this->clearPathEntity();
            }

            $this->ticksAtLastPos = $this->totalTicks;
            $this->lastPosCheck = $pos;
        }
    }

    public function noPath(): bool {
        return $this->currentPath === null || $this->currentPath->isFinished();
    }

    public function clearPathEntity(): void {
        $this->currentPath = null;
    }

    protected abstract function getEntityPosition(): Vector3;

    protected abstract function canNavigate(): bool;

    protected function isInLiquid(): bool {
        return $this->theEntity->isUnderwater(); // LAVA
    }

    protected abstract function isDirectPathBetweenPoints(Vector3 $pos1, Vector3 $pos2, int $sizeX, int $sizeY, int $sizeZ): bool;
}