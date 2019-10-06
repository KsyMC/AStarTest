<?php

declare(strict_types=1);

namespace k0si\astartest\entity;

use pocketmine\network\mcpe\protocol\types\entity\EntityLegacyIds;

class CustomZombie extends PathFindingLiving {
    
    const NETWORK_ID = EntityLegacyIds::ZOMBIE;

    public $width = 0.6;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    public function getName(): string {
        return "Zombie";
    }
}