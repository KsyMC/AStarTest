<?php

declare(strict_types=1);

namespace k0si\astartest\entity;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Living;
use pocketmine\entity\Human;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\block\BlockFactory;
use k0si\astartest\AStar\Helper;
use k0si\astartest\AStar\AStar;

abstract class CustomLiving extends Living implements Helper {

    /** @var AStar */
    protected $pathFinder = null;

    /** @var Vector3[] */
    protected $paths = [];

    /** @var int */
    protected $pathOffset = 0;

    /** @var Vector3|null */
    private $startPos;

    /** @var Vector3|null */
    private $endPos;

    /** @var int */
    private $delay = 0;

    /** @var int[] */
    private $cantJumpBlocks = [
        ItemIds::FENCE,
        ItemIds::FENCE_GATE,
        ItemIds::OAK_FENCE_GATE,
        ItemIds::NETHER_BRICK_FENCE,
        ItemIds::SPRUCE_FENCE_GATE,
        ItemIds::BIRCH_FENCE_GATE,
        ItemIds::JUNGLE_FENCE_GATE,
        ItemIds::DARK_OAK_FENCE_GATE,
        ItemIds::ACACIA_FENCE_GATE
    ];
    
    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);

        $this->pathFinder = new AStar($this);
    }

    protected function entityBaseTick(int $tickDiff = 1): bool {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if ($this->delay > 0) {
            $this->delay--;
            return $hasUpdate;
        }

        if ($this->endPos === null) {
            $entity = $this->getWorld()->getNearestEntity($this->getPosition(), 10, Human::class);
            if ($entity !== null) {
                $this->endPos = $entity->getPosition()->asVector3()->floor();
            }
        }

        if ($this->endPos !== null && empty($this->paths)) {
            $this->startPos = $this->getPosition()->asVector3()->floor();
            $this->paths = $this->pathFinder->find($this->startPos);

            if (empty($this->paths)) {
                $this->endPos = null;
                $this->delay = 40;
            }
        }

        if (!empty($this->paths)) {
            $nextPos = $this->paths[$this->pathOffset];
            $nextPos = $nextPos->add(0.5, 0, 0.5);

            $x = $nextPos->x - $this->getPosition()->getX();
            $y = $nextPos->y - $this->getPosition()->getY();
            $z = $nextPos->z - $this->getPosition()->getZ();
            $diff = abs($x) + abs($z);
            if ($diff !== 0.0) {
                $hasUpdate = true;
                $ground = $this->onGround ? 0.125 : 0.0025;
                $speed = 0.4;
                $this->motion->x += $speed * $ground * $x / $diff;
                $this->motion->z += $speed * $ground * $z / $diff;
            }

            if ($hasUpdate && $this->onGround) {
                if ($y > 0) {
                    $this->motion->y += 0.52;
                }
            }
            $this->setRotation(rad2deg(atan2($z, $x)) - 90.0, 0.0);

            if ($this->getPosition()->distanceSquared($nextPos) < 0.15) {
                $this->pathOffset++;
                if ($this->pathOffset >= count($this->paths)) {
                    $this->pathOffset = 0;
                    $this->paths = [];
                    $this->endPos = null;
                }
            }
        }

        return $hasUpdate;
    }

    public function getNeighbor(object $data): array {
        if ($data instanceof Vector3) {
            $directions = [ [-1, 0], [1, 0], [0, -1], [0, 1] ];
            $positions = [];

            /**
             * T: overhead
             * H: head
             * F: feet (cur position)
             * 
             * --123
             * TA
             * HB
             * FC
             * -D
             * -E
             */

            foreach ($directions as $direction) {
                $x = $direction[0];
                $z = $direction[1];

                $posB = $data->add($x, 1, $z);
                $blockB = $this->getWorld()->getBlock($posB);
                if ($blockB->isSolid()) {
                    continue;
                }

                $posC = $data->add($x, 0, $z);
                $blockC = $this->getWorld()->getBlock($posC);
                if ($blockC->isSolid()) {
                    if (in_array($blockC->getId(), $this->cantJumpBlocks)) {
                        continue;
                    }
                    $posA = $data->add($x, 2, $z);
                    $blockA = $this->getWorld()->getBlock($posA);
                    if ($blockA->isSolid()) {
                        continue;
                    }
                    $positions[] = $posB;
                    continue;
                }

                $posD = $data->add($x, -1, $z);
                $blockD = $this->getWorld()->getBlock($posD);
                if ($blockD->isSolid()) {
                    $positions[] = $posC;
                    continue;
                }

                $posE = $data->add($x, -2, $z);
                $blockE = $this->getWorld()->getBlock($posE);
                if ($blockE->isSolid()) {
                    $positions[] = $posD;
                }
            }
            return $positions;
        }
        return [];
    }

    public function getDistance(object $data1, object $data2): float {
        if ($data1 instanceof Vector3 && $data2 instanceof Vector3) {
            return $data1->distance($data2);
        }
        return -1;
    }

    public function getHeuristic(object $data): float {
        if ($data instanceof Vector3) {
            return $this->getDistance($data, $this->endPos);
        }
        return -1;
    }

    public function isEnd(object $data): bool {
        if ($data instanceof Vector3) {
            return $data->x === $this->endPos->x && $data->y === $this->endPos->y && $data->z === $this->endPos->z;
        }
        return false;
    }

    public function hash(object $data): string {
        if ($data instanceof Vector3) {
            return $data->x . "," . $data->y . "," . $data->z;
        }
        return "";
    }
}