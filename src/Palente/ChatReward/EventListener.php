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
        if(!$this->plugin->existData($player->getName())){
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
        if(isset($this->lastMessage[$name]) && (time() - $this->lastMessage[$name]) < $this->plugin->cooldownChat) return;
        $this->lastMessage[$name] = time();
        $points = $this->plugin->addPoints($player);
        // debug
        $player->sendMessage("Plus ".$points);
    }
}