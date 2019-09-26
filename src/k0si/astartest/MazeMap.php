<?php

declare(strict_types=1);

namespace k0si\astartest;

use pocketmine\math\Vector2;
use k0si\astartest\AStar\Helper;
use k0si\astartest\AStar\AStar;

class MazeMap implements Helper {
    public $map = [
        ["#", "#", "#", "#", "#", "#", "#", "#", "#"],
        ["#", "S", "#", " ", " ", " ", " ", " ", "#"],
        ["#", " ", "#", " ", "#", " ", "#", " ", "#"],
        ["#", " ", "#", " ", "#", " ", " ", " ", "#"],
        ["#", " ", "#", " ", "#", " ", "#", " ", "#"],
        ["#", " ", " ", " ", "#", " ", " ", "E", "#"],
        ["#", "#", "#", "#", "#", "#", "#", "#", "#"]
    ];

    /** @var Vector2 */
    private $startPos;

    /** @var Vector2 */
    private $endPos;

    public function start() {
        $this->startPos = new Vector2(1, 1);
        $this->endPos = new Vector2(7, 5);

        $astar = new AStar($this);
        echo implode(',', $astar->find($this->startPos));
    }

    public function getNeighbor(object $data): array {
        if ($data instanceof Vector2) {
            $positions = [];
            for ($xi = $data->x - 1; $xi <= $data->x + 1; $xi++) {
                for ($yi = $data->y - 1; $yi <= $data->y + 1; $yi++) {
                    if (abs($xi) == 1 && abs($yi) == 1) continue;

                    $block = $this->map[$yi][$xi];
                    if ($block !== "#") {
                        $positions[] = new Vector2($xi, $yi);
                    }
                }
            }
            return $positions;
        }
        return [];
    }

    public function getDistance(object $data1, object $data2): float {
        if ($data1 instanceof Vector2 && $data2 instanceof Vector2) {
            $dx = $data2->x - $data1->x;
            $dy = $data2->y - $data1->y;
            return sqrt($dx * $dx + $dy * $dy);
        }
        return -1;
    }

    public function getHeuristic(object $data): float {
        if ($data instanceof Vector2) {
            return $this->getDistance($data, $this->endPos);
        }
        return -1;
    }

    public function isEnd(object $data): bool {
        return $data == $this->endPos;
    }

    public function hash(object $data): string {
        if ($data instanceof Vector2) {
            return $data->x . "," . $data->y;
        }
        return "";
    }
}