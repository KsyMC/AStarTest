<?php

declare(strict_types=1);

namespace k0si\astartest\pathfinding;

use pocketmine\math\Vector3;
use pocketmine\item\ItemIds;
use pocketmine\world\World;
use k0si\astartest\entity\PathFindingLiving;

class PathNavigateGround extends PathNavigate {

    /** @var WalkNodeProcessor */
    protected $nodeProcessor;

    public function __construct(PathFindingLiving $entity, World $world) {
        parent::__construct($entity, $world);
    }

    protected function getPathFinder(): PathFinder {
        $this->nodeProcessor = new WalkNodeProcessor();
        return new PathFinder($this->nodeProcessor);
    }

    protected function canNavigate(): bool {
        //                                   getCanSwim
        return $this->theEntity->onGround || true && $this->isInLiquid();
    }

    protected function getEntityPosition(): Vector3 {
        $pos = $this->theEntity->getPosition();
        return new Vector3($pos->x, $pos->y, $pos->z);
    }

    private function getPathablePosY(): int {
        if ($this->theEntity->isUnderwater()) {
            $i = (int) $this->theEntity->getBoundingBox()->minY;
            $pos = $this->theEntity->getPosition()->floor();
            $block = $this->world->getBlockAt((int) floor($pos->x), $i, (int) floor($pos->z));
            $j = 0;

            while ($block->getId() == ItemIds::FLOWING_WATER || $block->getId() == ItemIds::WATER) {
                $i++;
                $block = $this->world->getBlockAt(floor($pos->x), $i, floor($pos->z));
                $j++;

                if ($j > 16) {
                    return (int) $this->theEntity->getBoundingBox()->minY;
                }
            }
            return $i;
        } else {
            return (int) ($this->theEntity->getBoundingBox()->minY + 0.5);
        }
    }

    protected function isDirectPathBetweenPoints(Vector3 $pos1, Vector3 $pos2, int $sizeX, int $sizeY, int $sizeZ): bool {
        $diffX = (float) $pos2->x - $pos1->x + 0.01;
        $diffZ = (float) $pos2->z - $pos1->z + 0.01;
        $distanceSquared = $diffX * $diffX + $diffZ * $diffZ;

        if ($distanceSquared < 0.00000001) {
            return false;
        } else {
            $distance = 1.0 / sqrt($distanceSquared);
            $diffX = $diffX * $distance;
            $diffZ = $diffZ * $distance;
            $sizeX = $sizeX + 2;
            $sizeZ = $sizeZ + 2;

            if (!$this->isSafeToStandAt((int) floor($pos1->x), (int) $pos1->y, (int) floor($pos1->z), $sizeX, $sizeY, $sizeZ, $pos1, $diffX, $diffZ)) {
                return false;
            } else {
                $sizeX = $sizeX - 2;
                $sizeZ = $sizeZ - 2;
                $a = 1.0 / abs($diffX);
                $b = 1.0 / abs($diffZ);
                $c = (floor($pos1->x) * 1) - $pos1->x;
                $d = (floor($pos1->z) * 1) - $pos1->z;

                if ($diffX >= 0.0) {
                    $c++;
                }

                if ($diffZ >= 0.0) {
                    $d++;
                }

                $c = $c / $diffX;
                $d = $d / $diffZ;
                $e = $diffX < 0.0 ? -1 : 1;
                $f = $diffZ < 0.0 ? -1 : 1;
                $diffX2 = (int) (floor($pos2->x) - floor($pos1->x));
                $diffZ2 = (int) (floor($pos2->z) - floor($pos1->z));

                $floorX = (int) floor($pos1->x);
                $floorZ = (int) floor($pos1->z);
                while ($diffX2 * $e > 0 || $diffZ2 * $f > 0) {
                    if ($c < $d) {
                        $c += $a;
                        $floorX += $e;
                        $diffX2 = (int) (floor($pos2->x) - $floorX);
                    } else {
                        $d += $b;
                        $floorZ += $f;
                        $diffZ2 = (int) (floor($pos2->z) - $floorZ);
                    }

                    if (!$this->isSafeToStandAt($floorX, (int) $pos1->y, (int) floor($pos1->z), $sizeX, $sizeY, $sizeZ, $pos1, $diffX, $diffZ)) {
                        return false;
                    }
                }
                return true;
            }
        }
    }

    private function isSafeToStandAt(int $x, int $y, int $z, int $sizeX, int $sizeY, int $sizeZ, Vector3 $originPos, float $vecX, float $vecZ): bool {
        $minX = (int) ($x - $sizeX / 2);
        $minZ = (int) ($z - $sizeZ / 2);

        if (!$this->isPositionClear($minX, $y, $minZ, $sizeX, $sizeY, $sizeZ, $originPos, $vecX, $vecZ)) {
            return true;
        } else {
            for ($i = $minX; $i < $minX + $sizeX; $i++) {
                for ($j = $minZ; $j < $minZ + $sizeZ; $j++) {
                    $a = $i + 0.5 - $originPos->x;
                    $b = $j + 0.5 - $originPos->z;

                    if ($a * $vecX + $b * $vecZ >= 0.0) {
                        $block = $this->world->getBlockAt($i, $y - 1, $j); 
                        if ($block->getId() == ItemIds::AIR) {
                            return false;
                        }

                        if ($block->getId() == ItemIds::WATER && !$this->theEntity->isUnderwater()) {
                            return false;
                        }

                        if ($block->getId() == ItemIds::LAVA) {
                            return false;
                        }
                    }
                }
            }
            return true;
        }
    }

    private function isPositionClear(int $x, int $y, int $z, int $sizeX, int $sizeY, $sizeZ, Vector3 $originPos, float $vecX, float $vecZ): bool {
        /*for ($blockPos as $) {
            $a = ;
            $b = ;

            if ($a * $vecX + $b * $vecZ >= 0.0) {
                $block = $this->world->getBlock();
                if () {
                    return false;
                }
            }
        }*/
        return true;
    }
}