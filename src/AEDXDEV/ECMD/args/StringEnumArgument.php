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
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\Server;
use function array_keys;
use function array_map;
use function implode;
use function preg_match;
use function strtolower;

abstract class StringEnumArgument extends BaseArgument{

	protected static array $VALUES = [];

	public function __construct(string $name, bool $optional = false) {
		parent::__construct($name, $optional);
		$this->parameterData->enum = new CommandEnum("", $this->getEnumValues());
	}

	public function getNetworkType(): int{
		// this will be disregarded by PM anyways because this will be considered as a string enum
		return -1;
	}

	public function canParse(string $testString, CommandSender $sender): bool{
		return (bool)preg_match(
			"/^(" . implode("|", array_map("\\strtolower", $this->getEnumValues())) . ")$/iu",
			$testString
		);
	}
	
	public function parse(string $argument, CommandSender $sender): string{
		return $this->getValue($argument);
	}

	public function getValue(string $string): mixed{
		return static::$VALUES[strtolower($string)];
	}

	public function getEnumValues(): array{
		return array_keys(static::$VALUES);
	}
	
	public static function tick(): void{}
}
