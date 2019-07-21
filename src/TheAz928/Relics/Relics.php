<?php
namespace TheAz928\Relics;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\level\particle\LavaParticle;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Relics extends PluginBase implements Listener {

    public const VERSION = "1.0.2";

    /** @var RelicType[] */
    protected $relics = null;

    /** @var Item[] */
    private $applicable = null;

    public function onLoad() {
        $this->saveDefaultConfig();

        if(($v = $this->getConfig()->get("version")) !== self::VERSION){
            $this->getLogger()->info("Incompatible config version detected, please update your new config...");

            rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config." . $v . ".yml");
            $this->saveResource("config.yml");
            $this->getConfig()->reload();
        }

        $this->applicable = [];
        $this->relics = [];
        foreach($this->getConfig()->get("applicable") as $item){
            $this->applicable[] = Item::fromString($item);
        }
        foreach($this->getConfig()->get("relics") as $key => $data){
            try{
                $this->relics[] = new RelicType($data);
            }catch(\Exception $exception){
                $this->getLogger()->logException($exception);
            }
        }
    }

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
     * @param BlockBreakEvent $event
     * @priority MONITOR
     * @ignoreCancelled true
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $continue = false;

        if($block->y <= $this->getConfig()->getNested("spawn-settings.maxY") and $block->y >= $this->getConfig()->getNested("spawn-settings.minY")){
            foreach($this->applicable as $item){
                if($block->getId() == $item->getId() and ($block->getDamage() == $item->getDamage() or $item->getDamage() == -1)){
                    $continue = true;
                }
            }
            if($continue){
                foreach($this->relics as $relic){
                    if(mt_rand(0, 999) < $relic->getSpawnChance()){
                        $player->sendTip(TextFormat::GRAY . "You've found a relic: " . $relic->getName());
                        if($relic->getBroadcast() !== null){
                            $this->getServer()->broadcastMessage(TextFormat::colorize(str_replace("{player}", $player->getDisplayName(), $relic->getBroadcast())));
                        }
                        if($this->getConfig()->get("particles")){
                            for($i = 0; $i < 360; $i += 5){
                                $player->getLevel()->addParticle(new LavaParticle(new Vector3($block->x + sin($i), $block->y + 0.5, $block->z + -cos($i))));
                            }
                        }

                        $items = $relic->getRandomItems();
                        $chest = Item::get(Item::CHEST)->setCustomName($relic->getName())->setLore($relic->getLore());

                        $list = [];

                        $tried = 0;
                        while(count($items) > 0 and $tried < 100){
                            $tried++;
                            $slot = mt_rand(0, 26);
                            if(isset($list[$slot])){
                                continue;
                            }

                            /** @var Item $item */
                            $item = array_shift($items);
                            $list[$slot] = $item->nbtSerialize($slot);
                        }
                        for($i = 0; $i < 27; $i++){
                            if(isset($list[$i])){
                                continue;
                            }

                            $list[$i] = Item::get(Item::COBBLESTONE)->nbtSerialize($i);
                        }

                        $chest->setCustomBlockData(new CompoundTag("", [new ListTag("Items", array_values($list), NBT::TAG_Compound), new StringTag("CustomName", $relic->getName())]));
                        $player->getLevel()->dropItem($block, $chest);
                        break;
                    }
                }
            }
        }
    }
}