<?php
namespace TheAz928\Relics;

use pocketmine\utils\TextFormat;

class RelicType {

    /** @var string */
    protected $name = "";

    /** @var int */
    protected $maxItems = 1;

    /** @var int */
    protected $spawnChance = 0;

    /** @var array */
    protected $items = [];

    /** @var array */
    protected $lore = [];

    /** @var null|string */
    protected $broadcast = null;

    /**
     * RelicType constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->name = TextFormat::colorize($data["name"]);
        $this->broadcast = $data["broadcast"] ?? null;
        $this->maxItems = $data["max-items"];
        $this->spawnChance = $data["spawn-chance"];
        $this->lore = array_map(function($st) {
            return TextFormat::colorize($st);
        }, $data["lore"] ?? []);
        $this->items = array_map(function(array $dat) {
            return new AncientItem($dat);
        }, $data["items"] ?? []);
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getItems(): array {
        return $this->items;
    }

    /**
     * @return array
     */
    public function getLore(): array {
        return $this->lore;
    }

    /**
     * @return int
     */
    public function getMaxItems(): int {
        return $this->maxItems;
    }

    /**
     * @return int
     */
    public function getSpawnChance(): int {
        return $this->spawnChance;
    }

    /**
     * @return null|string
     */
    public function getBroadcast(): ?string {
        return $this->broadcast;
    }

    /**
     * @return array
     */
    public function getRandomItems(): array {
        $added = 0;
        $tried = 0;
        $items = [];
        while($added < $this->maxItems and $tried < 100){
            $tried++;
            shuffle($this->items);
            /** @var AncientItem $item */
            $item = $this->items[array_rand($this->items)];

            if(mt_rand(0, 99) <= $item->getChance()){
                $items[] = $item->getItem();
                $added++;
            }
        }

        return $items;
    }
}