<?php

declare(strict_types=1);

namespace k0si\astartest\AStar;

use k0si\astartest\AStar\Node;

class AStar {

    /** @var Helper */
    private $helper = null;


    /** @var object[] */
    private $closeList = [];

    /** @var Node[] */
    private $openList = [];

    /** @var Node[] node data => Node */
    private $openNodeIdentifier = [];

    public function __construct(Helper $helper) {
        $this->helper = $helper;
    }

    /**
     * @param mixed $startData
     * @return int[] datas
     */
    public function find(object $startData): array {
        $startNode = new Node(null, $startData);

        $this->openNode[] = $startNode;
        $this->openNodeIdentifier[$this->helper->hash($startData)] = $startNode;

        $startTime = time();
        while(!empty($this->openNode)) {
            if (time() - $startTime > 5) break;
            usort($this->openNode, array($this, 'fsort'));

            $curNode = array_shift($this->openNode);
            unset($this->openNodeIdentifier[$this->helper->hash($curNode->data)]);

            if ($this->helper->isEnd($curNode->data)) {
                return $this->getPath($curNode);
            }

            $this->closeList[] = $this->helper->hash($curNode->data);

            $neighbors = $this->helper->getNeighbor($curNode->data);
            foreach ($neighbors as $neighbor) {
                if (in_array($this->helper->hash($neighbor), $this->closeList)) continue;

                $newG = $curNode->g + $this->helper->getDistance($curNode->data, $neighbor);
                $newNode = $this->openNodeIdentifier[$this->helper->hash($neighbor)] ?? null;
                $update = false;
                if ($newNode === null) {
                    $newNode = new Node($curNode, $neighbor);
                } else {
                    if ($newNode->g < $newG) {
                        continue;
                    }
                    $update = true;
                }

                $newNode->g = $newG;
                $newNode->h = $this->helper->getHeuristic($neighbor);
                $newNode->f = $newG + $newNode->h;

                if (!$update) {
                    $this->openNode[] = $newNode;
                }
                $this->openNodeIdentifier[$this->helper->hash($neighbor)] = $newNode;
            }
        }
        return [];
    }

    public static function fsort(Node $a, Node $b): int {
        if ($a === $b) return 0;
        return ($a->f < $b->f) ? -1 : 1;
    }

    /**
     * @return int[] tag datas
     */
    public function getPath(Node $node): array {
        if ($node->parent !== null) {
            $arr = $this->getPath($node->parent);
            $arr[] = $node->data;
            return $arr;
        } else {
            return [$node->data];
        }
    }
}