<?php

/**
  *  A free plugin for PocketMine-MP.
  *	
  *	Copyright (c) AEDXDEV
  *  
  *	Youtube: AEDX DEV 
  *	Discord: aedxdev
  *	Github: AEDXDEV
  *	Email: aedxdev@gmail.com
  *	Donate: https://paypal.me/AEDXDEV
  *   
  *        This program is free software: you can redistribute it and/or modify
  *        it under the terms of the GNU General Public License as published by
  *        the Free Software Foundation, either version 3 of the License, or
  *        (at your option) any later version.
  *
  *        This program is distributed in the hope that it will be useful,
  *        but WITHOUT ANY WARRANTY; without even the implied warranty of
  *        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *        GNU General Public License for more details.
  *
  *        You should have received a copy of the GNU General Public License
  *        along with this program.  If not, see <http://www.gnu.org/licenses/>.
  *   
  */

declare(strict_types=1);

namespace AEDXDEV\ECMD\args;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

class PlayerArgument extends StringEnumArgument{

  public function getTypeName(): string{
    return "string";
  }

  public function canParse(string $testString, CommandSender $sender): bool{
    return Server::getInstance()->getPlayerExact(str_replace('"', '', $testString)) !== null;
  }

  public function getValue(string $string): mixed{
    return Server::getInstance()->getPlayerExact(str_replace('"', '', $string));
  }
  
  public static function tick(): void{
    $players = array_map(
      fn(Player $p) => str_contains($p->getName(), " ") ? ("\"" . $p->getName() . "\"") : $p->getName(),
      Server::getInstance()->getOnlinePlayers()
    );
    static::$VALUES = array_combine($players, $players);
    parent::tick();
  }
}