<?php
namespace TheAz928\Relics;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\nbt\JsonNbtParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class AncientItem {

    /** @var Item */
    protected $item;

    /** @var int */
    protected $chance = 0;

    /**
     * @param string $stem
     * @param null|string $customName
     * @param null|string $nbt
     * @param array $lore
     * @param array $enchants
     * @param bool $ignoreCount
     * @return Item
     */
    public static function buildItem(string $stem, ?string $customName, ?string $nbt, array $lore = [], array $enchants = [], bool $ignoreCount = false): Item {
        try{
            $values = explode(":", $stem);
            $item = Item::fromString($values[0]);
            $item->setDamage($values[1] ?? 0);
            $item->setCount($ignoreCount ? 1 : ($values[2] ?? 1));
            $item->setLore(array_map(function(string $str): string {
                return TextFormat::RESET . TextFormat::colorize($str);
            }, $lore));

            if($customName !== null){
                $item->setCustomName(TextFormat::colorize(str_replace("\n", "\n", $customName)));
            }

            foreach($enchants as $enchant){
                $enchant = explode(" ", $enchant);

                $type = Enchantment::getEnchantmentByName($enchant[0]) ?? Enchantment::getEnchantment((int)$enchant[0]);
                if($type !== null){
                    $item->addEnchantment(new EnchantmentInstance($type, (int)$enchant[1]));
                }
            }
            if($nbt !== null){
                /** @var CompoundTag $tag */
                $tag = JsonNbtParser::parseJson($nbt);
                $_tag = $item->getNamedTag();

                foreach($tag as $anotherTag){
                    $_tag->setTag($anotherTag, true);
                }
                $item->setNamedTag($_tag);
            }

            return $item;
        }catch(\Exception $exception){
            // wat
        }

        return Item::get(Item::AIR);
    }

    /**
     * AncientItem constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->item = self::buildItem($data["item"], $data["customName"] ?? null, $data["nbt"] ?? null, $data["lore"] ?? [], $data["enchants"] ?? [], false);
        $this->chance = $data["chance"];
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }

    /**
     * @return int
     */
    public function getChance(): int {
        return $this->chance;
    }
}