<?php

declare(strict_types=1);

namespace k0si\astartest\pathfinding;

use pocketmine\math\Vector3;
use pocketmine\item\ItemIds;
use pocketmine\world\World;
use pocketmine\entity\Entity;

class WalkNodeProcessor extends NodeProcessor {
    public function initProcessor(World $world, Entity $entity): void {
        parent::initProcessor($world, $entity);
    }

    public function getPathPointTo(Entity $entity): PathPoint {
        $y = 0;
        if ($entity->isUnderwater()) { // lava 포함
            $y = (int) $entity->getBoundingBox()->minY;
            $pos = $entity->getPosition();
            $blockPos = new Vector3((int) floor($pos->x), $y, (int) floor($pos->z));

            for ($block = $this->world->getBlock($blockPos); $block->getId() == ItemIds::FLOWING_WATER || $block->getId() == ItemIds::WATER; $block = $this->world->getBlock($blockPos)) {
                $y++;
                $blockPos->setComponents((int) floor($pos->x), $y, (int) floor($pos->z));
            }
        } else {
            $y = (int) floor($entity->getBoundingBox()->minY + 0.5);
        }
        return $this->openPoint((int) floor($entity->getBoundingBox()->minX), $y, (int) floor($entity->getBoundingBox()->minZ));
    }

    public function getPathPointToCoords(Entity $entity, float $x, float $y, float $z): PathPoint {
        return $this->openPoint((int) floor($x - ($entity->width / 2.0)), (int) floor($y), (int) floor($z - ($entity->width / 2.0)));
    }

    public function findPathOptions(array &$pathOptions, Entity $entity, PathPoint $currentPoint, PathPoint $targetPoint, float $maxDistance): int {
        $i = 0;
        $j = 0;

        if ($this->getVerticalOffset($entity, $currentPoint->x, $currentPoint->y + 1, $currentPoint->z) === 1) {
            $j = 1;
        }

        $point1 = $this->getSafePoint($entity, $currentPoint->x, $currentPoint->y, $currentPoint->z + 1, $j);
        $point2 = $this->getSafePoint($entity, $currentPoint->x - 1, $currentPoint->y, $currentPoint->z, $j);
        $point3 = $this->getSafePoint($entity, $currentPoint->x + 1, $currentPoint->y, $currentPoint->z, $j);
        $point4 = $this->getSafePoint($entity, $currentPoint->x, $currentPoint->y, $currentPoint->z - 1, $j);

        if ($point1 !== null && !$point1->visited && $point1->distanceTo($targetPoint) < $maxDistance) {
            $pathOptions[$i++] = $point1;
        }
        if ($point2 !== null && !$point2->visited && $point2->distanceTo($targetPoint) < $maxDistance) {
            $pathOptions[$i++] = $point2;
        }
        if ($point3 !== null && !$point3->visited && $point3->distanceTo($targetPoint) < $maxDistance) {
            $pathOptions[$i++] = $point3;
        }
        if ($point4 !== null && !$point4->visited && $point4->distanceTo($targetPoint) < $maxDistance) {
            $pathOptions[$i++] = $point4;
        }
        return $i;
    }

    private function getSafePoint(Entity $entity, int $x, int $y, int $z, int $j): ?PathPoint {
        $pathpoint = null;
        $flag = $this->getVerticalOffset($entity, $x, $y, $z);

        if ($flag == 2) {
            return $this->openPoint($x, $y, $z);
        } else {
            if ($flag == 1) {
                $pathpoint = $this->openPoint($x, $y, $z);
            }

            if ($pathpoint === null && $j > 0 && $flag != -3 && $flag != -4 && $this->getVerticalOffset($entity, $x, $y + $j, $z) == 1) {
                $pathpoint = $this->openPoint($x, $y + $j, $z);
                $y += $j;
            }

            if ($pathpoint !== null) {
                $fallHeight = 0;
                $flag = 0;

                for (; $y > 0; $pathpoint = $this->openPoint($x, $y, $z)) {
                    $flag = $this->getVerticalOffset($entity, $x, $y - 1, $z);

                    // avoids water
                    if (false && $flag == -1) {
                        return null;
                    }

                    if ($flag != 1) {
                        break;
                    }

                    /// $entity->getMaxFallHeight()
                    if ($fallHeight++ >= 3) {
                        return null;
                    }

                    $y--;

                    if ($y <= 0) {
                        return null;
                    }
                }

                if ($flag == -2) {
                    return null;
                }
            }
            return $pathpoint;
        }
    }

    private function getVerticalOffset(Entity $entity, int $x, int $y, int $z): int {
        return WalkNodeProcessor::getVerticalOffset_($this->world, $entity, $x, $y, $z, $this->entitySizeX, $this->entitySizeY, $this->entitySizeZ, false, true, true);
    }

    public static function getVerticalOffset_(World $world, Entity $entity, int $x, int $y, int $z, int $sizeX, int $sizeY, int $sizeZ, bool $avoidWater, bool $breakDoors, bool $enterDoors): int {
        $flag = false;
        $blockPos = $entity->getPosition()->asVector3()->floor();
        $blockPos2 = $blockPos->asVector3();

        for ($xi = $x; $xi < $x + $sizeX; $xi++) {
            for ($yi = $y; $yi < $y + $sizeY; $yi++) {
                for ($zi = $z; $zi < $z + $sizeZ; $zi++) {
                    $blockPos2->setComponents($xi, $yi, $zi);
                    $block = $world->getBlock($blockPos2);

                    if ($block->getId() !== ItemIds::AIR) {
                        if ($block->getId() !== ItemIds::TRAPDOOR && $block->getId() != ItemIds::IRON_TRAPDOOR) {
                            if ($block->getId() !== ItemIds::FLOWING_WATER && $block->getId() != ItemIds::WATER) {
                                if (!$enterDoors && $block instanceof WoodenDoor) {
                                    return 0;
                                }
                            } else {
                                if ($avoidWater) {
                                    return -1;
                                }
                                $flag = true;
                            }
                        } else {
                            $flag = true;
                        }

                        if ($world->getBlock($blockPos2) instanceof BaseRail) {
                            if (!($world->getBlock($blockPos) instanceof BaseRail) && !($world->getBlock($blockPos->down()) instanceof BaseRail)) {
                                return -3;
                            }
                        } else if ($block->isSolid() && (!$breakDoors || !($block instanceof WoodenDoor))) {
                            if ($block instanceof Fence || $block instanceof FenceGate || $block instanceof Wall) {
                                return -3;
                            }

                            if ($block->getId() == ItemIds::TRAPDOOR || $block->getId() == ItemIds::IRON_TRAPDOOR) {
                                return -4;
                            }

                            // Need lava test
                            return 0;
                        }
                    }
                }
            }
        }
        return $flag ? 2 : 1;
    }
}