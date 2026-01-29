<?php

/**
  *  A free library for PocketMine-MP.
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
use pocketmine\network\mcpe\protocol\types\command\CommandSoftEnum;
use function implode;
use function array_map;
use function array_keys;
use function preg_match;
use function spl_object_id;
use function strtolower;

abstract class StringEnumArgument extends BaseArgument{

  /** @var array<class-string<StringEnumArgument>, true> */
  protected static array $dynamicClasses = [];
	/** @var array<class-string<StringEnumArgument>, array<int, static>> */
  protected static array $instances = [];
  /** @var array<string, mixed> */
  protected static array $VALUES = [];
	/** @var CommandEnum */
  protected CommandSoftEnum $enum;

	public function __construct(string $name, bool $optional = false) {
		parent::__construct($name, $optional);
		self::$dynamicClasses[static::class] = true;
		self::$instances[static::class][spl_object_id($this)] = $this;
    $this->enum = new CommandSoftEnum($this->getEnumName(), $this->getEnumValues(), true);
    $this->parameterData->enum = $this->enum;
	}

	public function __destruct() {
    unset(static::$instances[static::class][spl_object_id($this)]);
	}

  public static function getDynamicClasses(): array{
    return array_keys(self::$dynamicClasses);
  }

	protected static function getActiveInstances(): array{
    return self::$instances[static::class] ?? [];
	}

  protected function getEnumName(): string{
    return "enum#" . static::class;
  }

	public function getNetworkType(): int{
		// PM ignores this for string enums
		return -1;
	}

  public function getEnumValues(): array{
		return array_keys(static::$VALUES);
	}

  public function getTypeName(): string{
    return "string";
  }

	public function canParse(string $testString, CommandSender $sender): bool{
		return isset(static::$VALUES[strtolower($testString)]) || isset(static::$VALUES[$testString]);
		/*return (bool)preg_match(
			"/^(" . implode("|", array_map("\\strtolower", $this->getEnumValues())) . ")$/iu",
			$testString
		);*/
	}
	
	public function parse(string $argument, CommandSender $sender): mixed{
		return $this->getValue($argument);
	}

	public function getValue(string $string): mixed{
		return static::$VALUES[strtolower($string)] ?? static::$VALUES[$string] ?? null;
	}
	
	public static function tick(): void{
	  foreach (static::getActiveInstances() as $instance) {
      $instance->enum = new CommandSoftEnum($instance->getEnumName(), $instance->getEnumValues());
      $instance->parameterData->enum = $instance->enum;
	  }
	}
}
