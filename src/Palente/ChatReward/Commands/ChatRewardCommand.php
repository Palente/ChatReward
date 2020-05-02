<?php
/*
 *  ChatReward is a plugin working under the software pmmp.
 *  Copyright (C) 2019-2020  Palente

 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.

 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
namespace Palente\ChatReward\Commands;

use Palente\ChatReward\ChatReward;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

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
        parent::__construct("chatreward", "ChatReward Command", "/chatreward", ["cr"]);
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
            $sender->sendMessage(TextFormat::GREEN."---------".TextFormat::DARK_GRAY."[ChatReward's Help Page]".TextFormat::GREEN."---------");
            $sender->sendMessage(TextFormat::RED."/chatreward".TextFormat::GREEN." info ".TextFormat::RESET."[player] => ".TextFormat::GRAY."Displays the level of the player");
            if($sender->isOp()){
                $sender->sendMessage(TextFormat::RED."/chatreward".TextFormat::GREEN." blacklist ".TextFormat::YELLOW."list ".TextFormat::RESET."=> ".TextFormat::GRAY."Displays the list of BlackListed Player.");
                $sender->sendMessage(TextFormat::RED."/chatreward".TextFormat::GREEN." blacklist ".TextFormat::YELLOW."add ".TextFormat::RESET."<player> => ".TextFormat::GRAY."Add a Player to the BlackList");
                $sender->sendMessage(TextFormat::RED."/chatreward".TextFormat::GREEN." blacklist ".TextFormat::YELLOW."remove ".TextFormat::RESET."<player> => ".TextFormat::GRAY."Remove a Player from the BlackList");
            }
            //TODO: Make a better help page
            return;
        }
        if (count($args) == 1) {
            if ($args[0] == "info") {
                if (!$sender instanceof Player) return;
                //Display his level and his exp
                $sender->sendMessage($this->plugin->prefix.TextFormat::YELLOW." Your information:");
                $sender->sendMessage(TextFormat::GREEN." You are level: {$this->plugin->getLevel($sender)}.\n You have {$this->plugin->getPoints($sender)} exp.");
                return;
            }
        }
        if(count($args) == 2){
            $action = strtolower($args[0]);
            if($action == "blacklist") {
                if (!$sender->isOp()) {
                    $sender->sendMessage($this->plugin->prefix . TextFormat::RED . "You are not allowed to use this command!");
                    return;
                }
                if (strtolower($args[1]) == "list") {
                    $blacklisteds = $this->plugin->getBlacklisted();
                    if (!$blacklisteds) {
                        $sender->sendMessage($this->plugin->prefix . TextFormat::RED . "No players are blacklisted!\n" . TextFormat::BLUE . "-> To add a player in the blacklist use : " . TextFormat::RESET . " /chatreward blacklist add <player>");
                        return;
                    }
                    $blacklistedsList = "";
                    foreach ($blacklisteds as $b) $blacklistedsList .= " - " . TextFormat::RED . $b . TextFormat::RESET . "\n";
                    $sender->sendMessage($this->plugin->prefix . TextFormat::RED . "There is " . count($blacklisteds) . " persons blacklisted " . TextFormat::RESET . ":\n$blacklistedsList");
                    return;
                }
            }
            if($action == "info"){
                $name = $args[1];
                $player = $this->plugin->getServer()->getPlayer($name);
                if(!$player instanceof Player){
                    $sender->sendMessage($this->plugin->prefix. TextFormat::RED. "The player '{$name}' is not online!");
                    return;
                }
                $sender->sendMessage($this->plugin->prefix.TextFormat::YELLOW." Information of ".$player->getName());
                $sender->sendMessage(TextFormat::GREEN." {$player->getName()} is level {$this->plugin->getLevel($player)}.\n He has {$this->plugin->getPoints($player)} exp.");
                return;
            }
        }
        //BLACKLIST
        if(count($args) == 3){
            $action = strtolower($args[0]);
            $name = strtolower($args[2]);
            if($action == "blacklist") {
                if (!$sender->isOp()) {
                    $sender->sendMessage($this->plugin->prefix.TextFormat::RED . "You are not allowed to use this command!");
                    return;
                }
                $actionBlacklist = strtolower($args[1]);
                if ($actionBlacklist == "add") {
                    if ($this->plugin->isBlacklisted($name)) {
                        $sender->sendMessage($this->plugin->prefix.TextFormat::RED . "The player '{$name}' is already blacklisted!\n" . TextFormat::BLUE . "-> To remove a player from blacklist use: " . TextFormat::RESET . "/chatreward remove <player>");
                        return;
                    }
                    $player = $this->plugin->getServer()->getPlayer($name);
                    if (!$player instanceof Player) {
                        $sender->sendMessage($this->plugin->prefix.TextFormat::RED . "You can't blacklist the player '$name' he is not online");
                        return;
                    }
                    $this->plugin->addBlacklist($player);
                    $sender->sendMessage($this->plugin->prefix.TextFormat::GREEN . "You successfuly blacklisted '" . TextFormat::YELLOW . $player->getName() . "'");
                    $player->sendMessage($this->plugin->prefix.TextFormat::RED . "You got BlackListed from The ChatReward System!");
                    return;
                }
                if ($actionBlacklist == "remove") {
                    if (!$this->plugin->isBlacklisted($name)) {
                        $sender->sendMessage($this->plugin->prefix.TextFormat::RED . "The player '{$name}' is not BlackListed!\n" . TextFormat::BLUE . "-> To add a player to the blacklist use: " . TextFormat::RESET . "/chatreward add <player>");
                        return;
                    }
                    $this->plugin->removeBlacklist($name);
                    $sender->sendMessage($this->plugin->prefix.TextFormat::GREEN . "The player '{$name}' got removed from blacklist");
                    return;
                }
            }
        }
    }
}