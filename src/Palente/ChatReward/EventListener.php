<?php


namespace Palente\ChatReward;


use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;

class EventListener implements Listener
{
    /** @var ChatReward $plugin */
    private $plugin;
    /** @var array $lastMessage */
    private $lastMessage = [];
    /**
     * EventListener constructor.
     * @param ChatReward $param
     */
    public function __construct(ChatReward $param)
    {
        $this->plugin = $param;
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if($this->plugin->existData($player->getName())){
            $this->plugin->initData($player->getName());
        }
    }

    public function onChat(PlayerChatEvent $event){
        if($event->isCancelled()) return;
        $player = $event->getPlayer();
        $name = strtolower($player->getName());
        $message = $event->getMessage();
        if($event->isCancelled()) return;
        if(strlen($message) < $this->plugin->minlenmess) return;
        if(isset($this->lastMessage[$name]) && (time() - $this->lastMessage) < $this->plugin->cooldownChat) return;
        $this->lastMessage[$name] = time();
        $this->plugin->addPoints($player, mt_rand(0, $this->plugin->ma_xp_message));


    }
}