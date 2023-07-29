<?php

namespace antbag\vouchers;

use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\item\VanillaItems;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;

use pocketmine\event\Listener;

class Main extends PluginBase implements Listener{

    public function onEnable() : void
    {
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
        switch($command->getName()) {
            case "voucher":
                if (!$sender->hasPermission("vouchers.command.use")){
                    $sender->sendMessage("§cYou do not have permissions to use this command");
                    return true;
                }
                
                switch (strtolower($args[0])) {
                    case "create":
                        if (!isset($args[1])) {
                            $sender->sendMessage("§l§cUsage: §r§a/voucher create <Player> <VoucherName> <Command>");
                            return true;
                        }
                        if (!$this->getServer()->getPlayerExact($args[1]) instanceof Player) {
                            $sender->sendMessage("§l§cERROR: §r§aYou have entered an invalid Player Username.");
                            return true;
                        }
                        if (!isset($args[2])) {
                            $sender->sendMessage("§l§cERROR: §r§aYou have entered an invalid Voucher Name.");
                            return true;
                        }
                        if (!isset($args[3])) {
                            $sender->sendMessage("§l§cERROR: §r§aYou have entered an invalid Command.");
                            return true;
                        }

                        $player = $this->getServer()->getPlayerExact($args[1]);
                        $player->sendMessage("§aYou have given " . $player->getname() . " a " . $args[2] . " Voucher!");
                        $item = VanillaItems::PAPER();
                        $item->setCustomName(str_replace("{VoucherName}", $args[2], $this->getConfig()->get("Voucher_Name")));
                        $item->setLore([str_replace("{VoucherName}", $args[2], $this->getConfig()->get("Voucher_Lore"))]);
                        $item->getNamedTag()->setString("Creator", $sender->getName());
                        $item->getNamedTag()->setString("Name", $args[2]);
                        array_shift($args);
                        array_shift($args);
                        array_shift($args);
                        $item->getNamedTag()->setString("Command", trim(implode(" ", $args)));
                        $item->getNamedTag()->setString("Voucher", "Voucher");
                        $player->getInventory()->addItem($item);
                        break;
                    case "info":
                        if (!$sender instanceof Player){
                            $sender->sendMessage("§cYou must be in-game to run this command");
                            return true;
                        }
                        $item = $sender->getInventory()->getItemInHand();
                        if (!$item->getNamedTag()->getString("Voucher")) {
                            $sender->sendMessage("§l§cError: §r§aYou must be holding a Voucher");
                            return true;
                        }
                        $sender->sendMessage("§aVoucher Created By: §b" . $item->getNamedTag()->getString("Creator") . "\n§aVoucher Creation Date/Time: §b" . date("Y-m-d H:i")  . "\n§aVoucher Command: §b/" . $item->getNamedTag()->getString("Command"));
                        break;
                    default:
                        $sender->sendMessage("§l§cUsage: §r§a/voucher <create/info>");
                        return true;
                }
        }
        return true;
    }

    public function onInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if (!$item->getNamedTag()->getTag("Voucher")) {
            return true;
        }
        $item->setCount($item->getCount() - 1);
        $player->getInventory()->setItemInHand($item);
        $player->sendMessage(str_replace("{VoucherName}", $item->getNamedTag()->getString("Name"), $this->getConfig()->get("Voucher_Claimed")));
        $this->getServer()->dispatchCommand(new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()), str_replace("{player}", '"' . $player->getName() . '"', $item->getNamedTag()->getString("Command")));
    }
}
