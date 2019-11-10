<?php


namespace Palente\ChatReward\Commands;


use Palente\ChatReward\ChatReward;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as TF;
class ChatRewardCommand extends Command
{
    /** @var ChatReward $plugin */
    private $plugin;

    /**
     * ChatRewardCommand constructor.
     * @param ChatReward $caller
     */
    public function __construct(ChatReward $caller)
    {
        $this->plugin = $caller;
        $this->setPermission("chatreward.command.chatreward");
        parent::__construct("chatreward", "ChatReward command", "/chatreward <info> [player]", ["cr"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$this->testPermission($sender))return false;
        if(count($args)==0){
            //HELP PAGE
            $sender->sendMessage(TF::GREEN."---------".TF::DARK_GRAY.$this->plugin->prefix.TF::GREEN."---------");
            $sender->sendMessage("Help Page: ");

            return true;
        }
        echo $args[0]."\n";
        if (count($args) == 1) {
            if ($args[0] == "about" || $args[0] == "info") {
                if (!$sender instanceof Player) {
                    $sender->sendMessage($this->plugin->prefix . "Plugin made by Palente");
                    return true;
                }
                else{
                    $sender->sendMessage($this->plugin->prefix."wow");
                }
            }
        }
        if(count($args) == 2){
            $action = strtolower($args[0]);
            if($action == "blacklist") {
                if (!$sender->isOp()) {
                    $sender->sendMessage($this->plugin->prefix . TextFormat::RED . "You are not allowed to use this command!");
                    return false;
                }
                if (strtolower($args[1]) == "list") {
                    $blacklisteds = $this->plugin->getBlacklisteds();
                    if (!$blacklisteds) {
                        $sender->sendMessage($this->plugin->prefix . TextFormat::RED . "No players are blacklisted!\n" . TextFormat::BLUE . "-> To add a player in the blacklist use : " . TextFormat::RESET . " /chatreward blacklist add <player>");
                        return true;
                    }
                    $blacklistedsList = "";
                    foreach ($blacklisteds as $b) $blacklistedsList .= " - " . TextFormat::RED . $b . TextFormat::RESET . "\n";
                    $sender->sendMessage($this->plugin->prefix . TextFormat::RED . "There is " . count($blacklisteds) . " persons blacklisted " . TextFormat::RESET . ":\n$blacklistedsList");
                    return true;
                }
            }
        }

        if(count($args) == 3){
            $action = strtolower($args[0]);
            if($action == "blacklist") {
                if (!$sender->isOp()) {
                    $sender->sendMessage($this->plugin->prefix.TextFormat::RED . "You are not allowed to use this command!");
                    return false;
                }
                $actionBlacklist = strtolower($args[1]);
                if ($actionBlacklist == "add") {
                    $name = strtolower($args[2]);
                    if ($this->plugin->isBlacklisted($name)) {
                        $sender->sendMessage($this->plugin->prefix.TextFormat::RED . "The player '{$name}' is already blacklisted!\n" . TextFormat::BLUE . " -> To remove a player from blacklist use: " . TextFormat::RESET . "/chatreward remove <player>");
                        return false;
                    }
                    $player = $this->plugin->getServer()->getPlayer($name);
                    if (!$player instanceof Player) {
                        $sender->sendMessage($this->plugin->prefix.TextFormat::RED . "You can't blacklist the player '$name' he is not online");
                        return false;
                    }
                    $this->plugin->addBlacklist($player);
                    $sender->sendMessage($this->plugin->prefix.TextFormat::GREEN . "You successfuly blacklisted '" . TextFormat::YELLOW . $player->getName() . "'");
                    $player->sendMessage($this->plugin->prefix.TextFormat::RED . "You got BlackListed from The ChatReward System!");
                    return true;
                }
                if ($actionBlacklist == "remove") {
                    $name = strtolower($args[2]);
                    if (!$this->plugin->isBlacklisted($name)) {
                        $sender->sendMessage($this->plugin->prefix.TextFormat::RED . "The player '{$name}' is not BlackListed!\n" . TextFormat::BLUE . " -> To add a player to the blacklist use: " . TextFormat::RESET . "/chatreward add <player>");
                        return false;
                    }
                    $this->plugin->removeBlacklist($name);
                    $sender->sendMessage($this->plugin->prefix.TextFormat::GREEN . "The player '{$name}' got removed from blacklist");
                    return true;
                }
            }
        }
    }
}