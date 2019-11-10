<?php


namespace Palente\ChatReward;


use Palente\ChatReward\Commands\ChatRewardCommand;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

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

    public $ma_xp_message= 10;

    public $economyApi;
    /**
     * @var Config
     */

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
        $this->ma_xp_message = $this->config->get("max_xp_per_message",10);
        $this->configPlayers = new Config($this->getDataFolder()."players.json", Config::JSON);
        //ADDON Code
        $addons = $this->config->get("addon_enabled");
        if(in_array("economyapi",$addons)){
            if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI")){
                $this->economyApi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
            } else {
                $this->getLogger()->error("You have enabled the usage of the plugin EconomyAPI but the plugin is not found.");
            }
        }
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
    public function getPoints(Player $player) : int{
        $name = strtolower($player->getName());
        if(!$this->existData($name)) return 0;
        return $this->configPlayers->get($name)["points"];
    }
    public function setPoints(Player $player, int $points){
        $name = strtolower($player->getName());
        if(!$this->existData($name)) $this->initData($name);
        $data = $this->configPlayers->get($name);
        $data["points"] = $points;
        $this->configPlayers->set($name, $data);
        $this->configPlayers->save(); $this->configPlayers->reload();
    }
    public function addPoints(Player $player, int $points){
        $this->setPoints($player, $this->getPoints($player)+$points);
    }
    public function getLevel(Player $player):?int{
        $name = strtolower($player->getName());
        if(!$this->existData($name)) return null;
        return $this->configPlayers->get($name)["level"];
    }
    public function setLevel(Player $player, int $level){
        $name = strtolower($player->getName());
        if(!$this->existData($name)) $this->initData($name);
        $data = $this->configPlayers->get($name);
        $data["level"] = $level;
        $this->configPlayers->set($name, $data);
        $this->configPlayers->save(); $this->configPlayers->reload();
    }
    public function addLevel(Player $player, int $level){
        $this->setLevel($player, $this->getLevel($player)+$level);
    }
}