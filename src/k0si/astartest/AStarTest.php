<?php

declare(strict_types=1);

namespace k0si\astartest;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\math\Vector2;
use pocketmine\item\ItemIds;
use pocketmine\block\RedstoneTorch;
use pocketmine\block\BlockFactory;
use pocketmine\world\Position;
use k0si\astartest\AStar\Helper;
use k0si\astartest\AStar\AStar;

class AStarTest extends PluginBase implements Listener, Helper {

    /** @var Position|null */
    private $startPos;

    /** @var Position|null */
    private $endPos;


    /** @var bool */
    private $started = false;

    /** @var bool */
    private $setMode = false;

    public function onLoad(): void {}

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->getServer()->getLogger()->info(TextFormat::BLUE . '[AStarTest] Plugin has been enabled');
    }

    public function onDisable(): void {
        $this->getServer()->getLogger()->info(TextFormat::BLUE . '[AStarTest] Plugin has been disabled');
    }

    public function onInteractEvent(PlayerInteractEvent $ev): void {
        $item = $ev->getItem();
        $block = $ev->getBlock();
        if ($item->getId() === ItemIds::STICK) {
            $ev->setCancelled();

            if (!$this->started) {
                if (!$this->setMode && $block->getId() === ItemIds::REDSTONE_TORCH && $this->startPos !== null && $this->endPos !== null) {
                    $ev->getPlayer()->sendMessage("시작");
                    $this->start();
                } else {
                    $this->setMode = !$this->setMode;
                    $ev->getPlayer()->sendMessage($this->setMode ? "놓기 모드 ON" : "놓기 모드 OFF");
                }
            } else {
                $ev->getPlayer()->sendMessage("종료");
                $this->stop();
            }
        }
    }

    public function onBlockPlaceEvent(BlockPlaceEvent $ev): void {
        $block = $ev->getBlock();
        if (!$this->started && $this->setMode && $block->getId() === ItemIds::STONE) {
            $ev->setCancelled();

            $torch = BlockFactory::get(ItemIds::REDSTONE_TORCH);
            if ($this->startPos === null) {
                $this->startPos = $block->getPos();

                $block->getPos()->getWorld()->setBlock($this->startPos, $torch);
                $ev->getPlayer()->sendMessage("시작 설정됨: ". $this->startPos);
            } else if ($this->endPos === null) {
                $this->endPos = $block->getPos();

                $torch->setLit(false);
                $block->getPos()->getWorld()->setBlock($this->endPos, $torch);
                $ev->getPlayer()->sendMessage("끝 설정됨: ". $this->endPos);
            } else {
                $block->getPos()->getWorld()->setBlock($this->startPos, BlockFactory::get(ItemIds::AIR));
                $block->getPos()->getWorld()->setBlock($this->endPos, BlockFactory::get(ItemIds::AIR));

                $this->startPos = null;
                $this->endPos = null;
                $ev->getPlayer()->sendMessage("초기화");
            }
        }
    }

    public function start(): void {
        $this->started = true;

        $astar = new AStar($this);
        $result = $astar->find($this->startPos);

        foreach ($result as $value) {
            if ($value instanceof Vector3) {
                $this->startPos->getWorld()->setBlock($value, BlockFactory::get(ItemIds::PLANKS));
            }
        }
        $this->started = false;
    }

    public function stop(): void {
        $this->started = false;

        // TODO : 중지 코드 작성
    }

    /// Helper implement ///

    public function getNeighbor(object $data): array {
        if ($data instanceof Vector3) {
            $positions = [];
            for ($xi = $data->x - 1; $xi <= $data->x + 1; $xi++) {
                for ($zi = $data->z - 1; $zi <= $data->z + 1; $zi++) {
                    if ($xi === $data->x && $zi === $data->z) continue;

                    $pos = new Vector3($xi, $this->startPos->y, $zi);
                    $block = $this->startPos->getWorld()->getBlock($pos);
                    if ($block->getId() === ItemIds::AIR || $block->getId() === ItemIds::UNLIT_REDSTONE_TORCH) {
                        $positions[] = $pos;
                    }
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
            return $data->x === $this->endPos->x && $data->z === $this->endPos->z;
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