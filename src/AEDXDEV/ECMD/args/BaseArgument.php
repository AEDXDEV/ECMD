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
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;

abstract class BaseArgument{
	/** @var CommandParameter */
	protected CommandParameter $parameterData;

	public function __construct(
	  protected string $name,
	  protected bool $optional = false
	) {
		$this->parameterData = new CommandParameter();
		$this->parameterData->paramName = $name;
		$this->parameterData->paramType = AvailableCommandsPacket::ARG_FLAG_VALID;
		$this->parameterData->paramType |= $this->getNetworkType();
		$this->parameterData->isOptional = $optional;
	}

	abstract public function getNetworkType(): int;

	abstract public function canParse(string $testString, CommandSender $sender): bool;

	abstract public function parse(string $argument, CommandSender $sender): mixed;

	public function getName(): string{
		return $this->name;
	}

	public function isOptional(): bool{
		return $this->optional;
	}

	public function getSpanLength(): int{
		return 1;
	}

	abstract public function getTypeName(): string;
	
	public function getNetworkParameterData(): CommandParameter{
    return $this->parameterData;
  }
}
