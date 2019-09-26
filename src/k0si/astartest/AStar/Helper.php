<?php

declare(strict_types=1);

namespace k0si\astartest\AStar;

interface Helper {

    /**
     * @return int[]
     */
    public function getNeighbor(object $data): array;

    /**
     * @return float
     */
    public function getDistance(object $data1, object $data2): float;

    /**
     * @return float
     */
    public function getHeuristic(object $data): float;

    /**
     * @return bool
     */
    public function isEnd(object $data): bool;

    /**
     * @return string
     */
    public function hash(object $data): string;
}