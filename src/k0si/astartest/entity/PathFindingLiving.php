<?php

declare(strict_types=1);

namespace k0si\astartest\entity;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Living;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use k0si\astartest\pathfinding\PathNavigateGround;

abstract class PathFindingLiving extends Living {

    /** @var PathNavigate */
    protected $navigator = null;
    
    /** @var EntityPath */
    private $entityPath;

    /// Move helper ///
    /** @var float */
    public $moveX = 0.0;

    /** @var float */
    public $moveY = 0.0;

    /** @var float */
    public $moveZ = 0.0;

    /** @var float */
    public $moveSpeed = 0.0;

    /** @var bool */
    public $moveUpdate = false;

    /// Change detect ///
    /** @var Player */
    private $attackPlayer = null;

    /** @var int */
    private $delayCount = 0;

    /** @var float */
    private $targetX = 0.0;

    /** @var float */
    private $targetY = 0.0;

    /** @var float */
    private $targetZ = 0.0;

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);

        $this->navigator = new PathNavigateGround($this, $this->getWorld());
    }

    protected function entityBaseTick(int $tickDiff = 1): bool {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if ($this->entityPath === null) {
            $players = $this->getWorld()->getPlayers();
            $entity = array_shift($players);// $this->getWorld()->getNearestEntity($this->getPosition(), 35, Human::class);
            if ($entity !== null) {
                $this->attackPlayer = $entity;
                $this->entityPath = $this->navigator->getPathToEntityLiving($entity);
                $this->navigator->setPath($this->entityPath, 1.0);
            }
        }

        $this->navigator->onUpdateNavigation();

        if ($this->moveUpdate) {
            $this->moveUpdate = false;

            $minY = (int) floor($this->getBoundingBox()->minY + 0.5);
            $x = $this->moveX - $this->getPosition()->x;
            $z = $this->moveZ - $this->getPosition()->z;
            $y = $this->moveY - $minY;
            $diff = $x * $x + $y * $y + $z * $z;

            if ($diff >= 0.00000025) {
                $yaw = (atan2($z, $x) * 180.0 / pi()) - 90.0;
                $this->setRotation($yaw, 0.0);
                
                $diff = abs($x) + abs($z);
                $hasUpdate = true;
                $ground = $this->onGround ? 0.125 : 0.0025;
                $speed = 0.4;
                $this->motion->x += $speed * $ground * $x / $diff;
                $this->motion->z += $speed * $ground * $z / $diff;

                if ($this->onGround && $y > 0.0 && $x * $x + $z * $z < 1.0) {
                    $this->motion->y += 0.52;
                }
            }
        }

        $attackPos = $this->attackPlayer->getPosition();
        $distance = $attackPos->distanceSquared(new Vector3($attackPos->x, $this->attackPlayer->getBoundingBox()->minY, $attackPos->z));
        $this->delayCount--;

        if ($this->delayCount <= 0 && ($this->targetX == 0.0 && $this->targetY == 0.0 && $this->targetZ || $attackPos->distanceSquared(new Vector3($this->targetX, $this->targetY, $this->targetZ)) >= 1.0)) {
            $this->targetX = $attackPos->x;
            $this->targetY = $this->attackPlayer->getBoundingBox()->minY;
            $this->targetZ = $attackPos->z;
            $this->delayCount = 4 + mt_rand(0, 6);

            if ($distance > 1024.0) {
                $this->delayCount += 10;
            } else if ($distance > 256.0) {
                $this->delayCount += 5;
            }

            if (!$this->navigator->tryMoveToEntityLiving($this->attackPlayer, 1.0)) {
                $this->delayCount += 15;
            }
        }

        return $hasUpdate;
    }

    private function limitAngle($yaw) {

    }
}