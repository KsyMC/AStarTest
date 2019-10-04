<?php

declare(strict_types=1);

namespace k0si\astartest;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
use pocketmine\item\ItemIds;
use pocketmine\entity\EntityFactory;
use pocketmine\network\mcpe\protocol\types\entity\EntityLegacyIds;

use k0si\astartest\entity\CustomZombie;

class AStarTest extends PluginBase implements Listener {

    public function onLoad(): void {
        EntityFactory::register(CustomZombie::class, ['Zombie', 'minecraft:zombie'], EntityLegacyIds::ZOMBIE);

        foreach(EntityFactory::getKnownTypes() as $k => $className) {
            /** @var Living|string $className */
            if(\is_a($className, EntityBase::class, \true) && $className::NETWORK_ID !== -1) {
                ItemFactory::register(new SpawnEgg(ItemIds::SPAWN_EGG, $className::NETWORK_ID, "Spawn " . (new \ReflectionClass($className))->getShortName(), $className), \true);
            }
        }
    }

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getLogger()->info(TextFormat::BLUE . '[AStarTest] Plugin has been enabled');
    }

    public function onDisable(): void {
        $this->getServer()->getLogger()->info(TextFormat::BLUE . '[AStarTest] Plugin has been disabled');
    }
}