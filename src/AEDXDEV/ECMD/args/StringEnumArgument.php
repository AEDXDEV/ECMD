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
use pocketmine\network\mcpe\protocol\types\command\CommandSoftEnum;
use pocketmine\Server;
use function array_keys;
use function array_map;
use function implode;
use function preg_match;
use function strtolower;

abstract class StringEnumArgument extends BaseArgument{

    /** @var array<class-string<StringEnumArgument>> */
    protected static array $dynamicClasses = [];

    protected static array $VALUES = [];
    protected static array $instances = [];

    public function __construct(string $name, bool $optional = false) {
        parent::__construct($name, $optional);

        // ✅ تسجيل الكلاس مرة واحدة فقط
        $class = static::class;
        if (!in_array($class, self::$dynamicClasses, true)) {
            self::$dynamicClasses[] = $class;
        }

        // إضافة instance للقائمة (تُستخدم لتحديث enum لكل object)
        self::$instances[spl_object_id($this)] = $this;

        // إعداد enum الافتراضي
        $this->enum = new \pocketmine\network\mcpe\protocol\types\command\CommandSoftEnum(
            "", $this->getEnumValues()
        );
        $this->parameterData->enum = $this->enum;
    }

    public function __destruct() {
        unset(self::$instances[spl_object_id($this)]);
    }

    public static function getDynamicClasses(): array {
        return self::$dynamicClasses;
    }

    public function getNetworkType(): int {
        return -1; // ignored for enums
    }

    public function canParse(string $testString, CommandSender $sender): bool {
        return (bool)preg_match(
            "/^(" . implode("|", array_map("\\strtolower", $this->getEnumValues())) . ")$/iu",
            $testString
        );
    }

    public function parse(string $argument, CommandSender $sender): mixed {
        return $this->getValue($argument);
    }

    public function getValue(string $string): mixed {
        return static::$VALUES[strtolower($string)] ?? $string;
    }

    public function getEnumValues(): array {
        return array_keys(static::$VALUES);
    }

    public static function tick(): void {
        foreach (self::$instances as $instance) {
            $instance->parameterData->enum = new \pocketmine\network\mcpe\protocol\types\command\CommandSoftEnum(
                "", $instance->getEnumValues()
            );
        }
    }
}

abstract class StringEnumArgument extends BaseArgument{

  /** @var array<class-string<StringEnumArgument>> */
  protected static array $dynamicClasses = [];
	/** @var static[] */
  protected static array $instances = [];
  /** @var array<string, mixed> */
  protected static array $VALUES = [];
	/** @var CommandEnum */
  protected CommandSoftEnum $enum;

	public function __construct(string $name, bool $optional = false) {
		parent::__construct($name, $optional);
		if (!in_array(static::class, self::$dynamicClasses, true)) {
		  self::$dynamicClasses[] = static::class;
		}
    static::$instances[spl_object_id($this)] = $this;
    $this->enum = new CommandSoftEnum("", $this->getEnumValues(), true);
    $this->parameterData->enum = $this->enum;
	}

	public function __destruct(){
    unset(static::$instances[spl_object_id($this)]);
	}

  public static function getDynamicClasses(): array{
    return self::$dynamicClasses;
  }

	protected static function getActiveInstances(): array{
    return static::$instances;
	}

	public function getNetworkType(): int{
		// PM ignores this for string enums
		return -1;
	}

	public function canParse(string $testString, CommandSender $sender): bool{
		return (bool)preg_match(
			"/^(" . implode("|", array_map("\\strtolower", $this->getEnumValues())) . ")$/iu",
			$testString
		);
	}
	
	public function parse(string $argument, CommandSender $sender): mixed{
		return $this->getValue($argument);
	}

	public function getValue(string $string): mixed{
		return static::$VALUES[strtolower($string)];
	}

	public function getEnumValues(): array{
		return array_keys(static::$VALUES);
	}
	
	public static function tick(): void{
		foreach (self::getActiveInstances() as $instance) {
      $instance->enum = new CommandSoftEnum("", $instance->getEnumValues(), true);
      $instance->parameterData->enum = $instance->enum;
		}
	}
}
