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
namespace Palente\ChatReward;

use Palente\ChatReward\Commands\ChatRewardCommand;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class ChatReward extends PluginBase
{
    /** @var Config $config */
    private $config;
    /** @var Config $configPlayers Configuration of players */
    private $configPlayers;
    /** @var string The actual version of the config*/
    const VERSION_CONFIG = "0.0.0";
    /** @var string $prefix the Prefix of the plugin */
    public $prefix = "[ChatReward]";
    /** @var int $cooldownChat Cooldown in seconds */
    public $cooldownChat = 3;
    /** @var int $minlenmess Minimum of lenth for a message to be count */
    public $minlenmess = 4;
    /** * @var int $ma_xp_message */
    public $max_xp_message= 10;
    private $announceEnabled;
    private $announceMessage;
    private $economyApi = null;
    private $purepermsApi = null;
    public function onLoad()
    {
        $this->getServer()->getCommandMap()->register("chatreward", new ChatRewardCommand($this));
    }

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        @mkdir($this->getDataFolder());
        if(!file_exists($this->getDataFolder()."config.yml")) $this->saveResource("config.yml");
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);
        $this->cooldownChat = $this->config->get("cooldown_message",3);
        $this->minlenmess = $this->config->get("mini_length_messag",4);
        $this->max_xp_message = $this->config->get("max_xp_per_message",10);
        $this->configPlayers = new Config($this->getDataFolder()."players.json", Config::JSON);
        $this->announceEnabled = $this->config->get("announce_level-up", true);
        $this->announceMessage = $this->config->get("announce_message", "§1[ChatReward]§f {playername} reached the level {level}");
        //ADDON Code
        $addons = $this->config->get("addons_enabled", []);
        if(in_array("economyapi",$addons)){
            if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI")) $this->economyApi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
            else $this->getLogger()->error("You have enabled the usage of the plugin EconomyAPI but the plugin is not found.");
        }
        if(in_array("pureperms",$addons)){
            if($this->getServer()->getPluginManager()->getPlugin("PurePerms")) $this->purepermsApi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
            else $this->getLogger()->error("You have enabled the usage of the plugin PurePerms but the plugin is not found.");
        }

        if(!is_null($this->economyApi) && !is_null($this->purepermsApi)) $this->getLogger()->notice("The support of EconomyAPI and PurePerms is enabled!");
    }

    /*
     * INTERNAL FUNCTION
     */
    public function getBlacklisteds() : ?array{
        return $this->config->get("blacklisted", null);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isBlacklisted(string $name):bool{
        $name = strtolower($name);
        if(in_array($name, $this->config->get("blacklisted", [])))return true;
        return false;
    }

    /**
     * @param Player $player
     */
    public function addBlacklist(Player $player){
        $name = strtolower($player->getName());
        $blacklisted =  $this->config->get("blacklisted");
        $blacklisted[] = $name;
        $this->config->set("blacklisted", $blacklisted);
        $this->config->save(); $this->config->reload();
    }

    /**
     * @param string $name
     */
    public function removeBlacklist(string $name){
        $name = strtolower($name);
        $blacklisted =  $this->config->get("blacklisted");
        //WTF did i do before?
        $blacklisted = array_merge(array_diff($blacklisted, array($name)));
        $this->config->set("blacklisted", $blacklisted);
        $this->config->save(); $this->config->reload();
    }
    public function initData(string $name) {
        $name= strtolower($name);
        $this->configPlayers->set($name, ["level"=>0, "points"=>0]);
        $this->configPlayers->save();
        $this->configPlayers->reload();
    }
    public function existData(string $name) : bool{
        return $this->configPlayers->exists($name, true);
    }
    private function getData(string $name) : ?array{
        $name = strtolower($name);
        if(!$this->existData($name)) return null;
        return $this->configPlayers->get($name);
    }
    private function setData(string $name, array $data){
        $this->configPlayers->set(strtolower($name), $data);
        $this->configPlayers->save();
        $this->configPlayers->reload();
    }
    public function getPoints(Player $player) : int{
        $name = strtolower($player->getName());
        if(!$this->existData($name)) return 0;
        return $this->getData($name)["points"];
    }
    public function addPoints(Player $player) :int{
        $points = mt_rand(1, $this->max_xp_message);
        if(!$this->checkReachedNextLevel($player, $points)) $this->setPoints($player, $this->getPoints($player)+$points);
        return $points;
    }
    private function setPoints(Player $player, int $points){
        $name = strtolower($player->getName());
        if(!$this->existData($name)) $this->initData($name);
        $data = $this->getData($name);
        $data["points"] = $points;
        $this->setData($name, $data);
    }

    /**
     * @param Player $player
     * @return int|null
     */
    public function getLevel(Player $player):?int{
        $name = strtolower($player->getName());
        if(!$this->existData($name)) return null;
        return $this->configPlayers->get($name)["level"];
    }

    /**
     * @param Player $player
     */
    public function addLevel(Player $player){
        $this->setLevel($player, $this->getLevel($player)+1);
        $this->setPoints($player, 0);
    }
    /**
     * @param Player $player
     * @param int $level
     */
    private function setLevel(Player $player, int $level){
        $name = strtolower($player->getName());
        if(!$this->existData($name)) $this->initData($name);
        $data = $this->getData($name);
        $data["level"] = $level;
        $this->setData($name, $data);
    }

    /**
     * This function check if the player reached the next level, if yes it call the function reachedNextLevel()
     * @param Player $player
     * @param int $xp
     * @return bool
     */
    private function checkReachedNextLevel(Player $player, int $xp) : bool{
        $level = $this->getLevel($player);
        if(isset($this->config->get("level_xp")[$level])){
            $xpToReach = $this->thisPluginIsAMathPlugin($level, $this->config->get("level_xp")[$level]);
            if($xpToReach == 0) return true;
            if(($this->getPoints($player)+$xp) >= $xpToReach)return true;
            return false;
        }else{
            if(isset($this->config->get("level_xp")["classic"])){
                $xpToReach = $this->thisPluginIsAMathPlugin($level, $this->config->get("level_xp")["classic"]);
                if($xpToReach == 0) return true;
                if(($this->getPoints($player)+$xp) >= $xpToReach)return true;
                return false;
            }else{
                //Couldn't check if he reached the good amount of xp to pass the next level
                //if we avert the console, it will litteraly spaam the console.
                return false;
            }
        }
    }
    private function reachedNextLevel(Player $player){
        $level = $this->getLevel($player)+1; #He reached a level so he reached the current level + 1
        if(isset($this->config->get("level_rewards")[$level])){
            $rewards = $this->config->get("level_rewards")[$level];
            if(isset($rewards["money"])){
                //give him money!
                $amount = $this->thisPluginIsAMathPlugin($level, $rewards["money"]);
                if ($amount != 0){
                    //We can give him money....
                    if(is_null($this->economyApi)){
                        //WHY DID YOU FILLED MONEY?
                        $this->getLogger()->warning("...");
                        $player->sendMessage(TextFormat::DARK_RED.$this->prefix."An error has occurred. Please contact an Administrator with this error: ".TextFormat::BOLD."ERR_PLUGIN_ECONOMY");
                        #i don't know if we let him have his other reward.
                    }else{
                        //Good Boy!
                        $this->economyApi->addMoney($player, $amount);
                    }
                }
                //else #why giving money if it's equal to 0? Just let it empty!
            }
            if(isset($rewards["rank"]) && $rewards["rank"] != ""){
                //give him a rank!
                if(is_null($this->purepermsApi)){
                    //WHY DID YOU FILLED rank?
                    $this->getLogger()->warning("...");
                    $player->sendMessage(TextFormat::DARK_RED.$this->prefix."An error has occurred. Please contact an Administrator with this error: ".TextFormat::BOLD."ERR_PLUGIN_RANK");
                    #i don't know if we let him have his other reward.
                }else{
                    $groupName = $rewards["rank"];
                    $group = $this->purepermsApi->getGroup($groupName);
                    if($group instanceof \_64FF00\PurePerms\PPGroup){
                        $this->purepermsApi->setGroup($player, $group);
                    }else{
                        //The rank don't exist!
                        $this->getLogger()->warning("An error has occurred when trying to give a reward to".$player->getName().". The rank '".$groupName."' don't exist");
                        $player->sendMessage(TextFormat::DARK_RED.$this->prefix."An error has occurred. Please contact an Administrator with this error: ".TextFormat::BOLD."ERR_RANK_DONT_EXIST");
                    }
                }
            }
            if(isset($rewards["message"]) && $rewards["message"] !=""){
                $player->sendMessage($this->replaceTags($rewards["message"], $player));
            }
            if($this->announceEnabled && $this->announceMessage != "")
                $this->getServer()->broadcastMessage($this->replaceTags($this->announceMessage, $player));

        }else{
            if(isset($this->config->get("level_rewards")["classic"])){
                $rewards = $this->config->get("level_rewards")["classic"];
                if(isset($rewards["money"])){
                    //give him money!
                    $amount = $this->thisPluginIsAMathPlugin($level, $rewards["money"]);
                    if ($amount != 0){
                        //We can give him money....
                        if(is_null($this->economyApi)){
                            //WHY DID YOU FILLED MONEY?
                            $this->getLogger()->warning("...");
                            $player->sendMessage(TextFormat::DARK_RED.$this->prefix."An error has occurred. Please contact an Administrator with this error: ".TextFormat::BOLD."ERR_PLUGIN_ECONOMY");
                            #i don't know if we let him have his other reward.
                        }else{
                            //Good Boy!
                            $this->economyApi->addMoney($player, $amount);
                        }
                    }
                    //else #why giving money if it's equal to 0? Just let it empty!
                }
                if(isset($rewards["rank"]) && $rewards["rank"] != ""){
                    //give him a rank!
                    if(is_null($this->purepermsApi)){
                        //WHY DID YOU FILLED rank?
                        $this->getLogger()->warning("...");
                        $player->sendMessage(TextFormat::DARK_RED.$this->prefix."An error has occurred. Please contact an Administrator with this error:".TextFormat::BOLD."ERR_PLUGIN_RANK");
                        #i don't know if we let him have his other reward.
                    }else{
                        $groupName = $rewards["rank"];
                        $group = $this->purepermsApi->getGroup($groupName);
                        if($group instanceof \_64FF00\PurePerms\PPGroup){
                            $this->purepermsApi->setGroup($player, $group);
                        }else{
                            //The rank don't exist!
                            $this->getLogger()->warning("An error has occurred when trying to give a reward to".$player->getName().". The rank '".$groupName."' don't exist");
                            $player->sendMessage(TextFormat::DARK_RED.$this->prefix."An error has occurred. Please contact an Administrator with this error: ".TextFormat::BOLD."ERR_RANK_DONT_EXIST");
                        }
                    }
                }
                if(isset($rewards["message"]) && $rewards["message"] !=""){
                    $player->sendMessage($this->replaceTags($rewards["message"], $player));
                }
                if($this->announceEnabled && $this->announceMessage != "")
                    $this->getServer()->broadcastMessage($this->replaceTags($this->announceMessage, $player));
            }else{
                //i did my best to make a good configuration, and you deleted the most important element?
                $this->getLogger()->critical("An error has occured. The configuration 'config.yml' is badly configurated! Please reset your configuration!");
                $player->sendMessage(TextFormat::DARK_RED.$this->prefix."An error has occurred. Please contact an Administrator with this error: ".TextFormat::BOLD."ERR_CONFIG_CLASSIC");

            }
        }
        $this->addLevel($player);
    }

    private function thisPluginIsAMathPlugin(int $level, string $calcul) : int{
        //https://stackoverflow.com/questions/18880772/calculate-math-expression-from-a-string-using-eval
        //I will add some more function like ln(4) and e^4
        $calcul = str_replace("{level}", $level, $calcul);
        if(preg_match('/(\d+)(?:\s*)([\+\-\*\/\^])(?:\s*)(\d+)/', $calcul, $matches) !== FALSE){
            $operator = $matches[2];
            $weDoMaths = 0;
            switch($operator){
                case '+':
                    $weDoMaths = $matches[1] + $matches[3];
                    break;
                case '-':
                    $weDoMaths = $matches[1] - $matches[3];
                    break;
                case '*':
                    $weDoMaths = $matches[1] * $matches[3];
                    break;
                case '/':
                    $weDoMaths = $matches[1] / $matches[3];
                    break;
                case '^':
                    $weDoMaths = $matches[1] ** $matches[3];
                    break;
            }
            return $weDoMaths;
        }else return intval($calcul);
    }
    private function replaceTags(string $messageWithTags, Player $player) :string{
        $message = str_replace("{playername}", $player->getName(), $messageWithTags);
        $message = str_replace("{level}", $this->getLevel($player)+1, $messageWithTags);

        return $message;
    }
}