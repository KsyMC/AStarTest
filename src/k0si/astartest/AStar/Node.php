<?php

declare(strict_types=1);

namespace k0si\astartest\AStar;

class Node {
    /** @var Node|null */
    public $parent;

    /** @var int */
    public $f = 0;

    /** @var int */
    public $g = 0;

    /** @var int */
    public $h = 0;

    /** @var object */
    public $data;

    public function __construct(?Node $parent, object $data) {
        $this->parent = $parent;
        $this->data = $data;
    }

    function __toString() {
        return "Node(data:" . $this->data . ")";
    }
}