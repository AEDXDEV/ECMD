<?php

declare(strict_types=1);

namespace AEDXDEV\ECMD\args;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;

abstract class BaseArgument {
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
		$this->parameterData->isOptional = $this->isOptional();
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
