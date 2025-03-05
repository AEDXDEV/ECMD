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

namespace AEDXDEV\ECMD;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\plugin\Plugin;
use pocketmine\player\Player;

use AEDXDEV\ECMD\args\BaseArgument;

use muqsit\simplepackethandler\SimplePacketHandler;

abstract class BaseCommand extends Command {

  private static bool $isIntercepting = false;

  private array $subCommands = [];
  private array $arguments = [];
  protected ?CommandSender $currentSender = null;

  public function __construct(
    private Plugin $plugin,
    string $name,
    string $description = "",
    array $aliases = []
  ) {
    parent::__construct($name, $description, null, $aliases);
    $this->prepare();
    $this->updateUsageMessage();
    $this->registerPacketHook();
  }

  abstract protected function prepare(): void;

  public function registerArgument(int $position, BaseArgument $argument): void{
    if ($position < 0) {
      throw new \InvalidArgumentException("Cannot register argument at negative position");
    }
    if ($position > 0 && !isset($this->arguments[$position - 1])) {
      throw new \InvalidArgumentException("Argument at position $position requires previous arguments");
    }
    if ($this->arguments[$position - 1]->isOptional() && !$argument->isOptional()) {
      throw new \LogicException("Cannot register required argument after optional");
    }
    $this->arguments[$position] = $argument;
    ksort($this->arguments);
  }

  public function registerSubCommand(
    string $name,
    array $arguments,
    callable $callback,
    ?string $permission = null,
    bool $console = true
  ): void{
    foreach ($arguments as $arg) {
      if (!$arg instanceof BaseArgument) {
        throw new \InvalidArgumentException("All arguments must be instances of BaseArgument");
      }
    }
    $this->subCommands[$name] = [
      "callback" => $callback,
      "permission" => $permission ?? null,
      "arguments" => $arguments,
      "console" => $console
    ];
  }

  private function updateUsageMessage(): void{
    $usages = ["/" . $this->getName() . " "];
    $formatArg = fn (BaseArgument $arg) => $arg->isOptional() ? " [" . $arg->getName() . "]" : " <" . $arg->getName() . ">";
    // Main command arguments
    if (!empty($this->arguments)) {
      $mainUsage = "/" . $this->getName();
      foreach ($this->arguments as $arg) {
        $mainUsage .= $formatArg($arg);
      }
      $usages[] = $mainUsage;
    }
    // Subcommands
    foreach ($this->subCommands as $name => $data) {
      $subUsage = "/" . $this->getName() . " " . $name;
      foreach ($data["arguments"] as $arg) {
        $subUsage .= $formatArg($arg);
      }
      $usages[] = $subUsage;
    }
    $this->usageMessage = implode("\n - ", $usages);
  }

  private function registerPacketHook(): void{
    SimplePacketHandler::createInterceptor($this->plugin)->interceptOutgoing(function(AvailableCommandsPacket $pk, NetworkSession $session): bool{
      if (($player = $session->getPlayer()) === null)return true;
      foreach ($pk->commandData as $cmdName => $cmdData) {
        if ($cmdName === $this->getName()) {
          $cmdData->overloads = $this->generateOverloads($player);
        }
      }
      $pk->softEnums = [];
      return true;
    });
  }

  private function generateOverloads(Player $player): array{
    $subOverloads = [];
    foreach ($this->subCommands as $name => $data) {
      if (!$data["permission"] || $player->hasPermission($data["permission"])) {
        $enum = new CommandEnum($name, [$name]);
        (fn() => $this->enumName = "enum#" . spl_object_id($enum))->call($enum);
        $param = CommandParameter::enum($name, $enum, 0, false);
        $subOverloads[] = new CommandOverload(false, [$param, ...array_map([$this, 'createArgumentParam'], $data["arguments"])]);
      }
    }
    return array_merge(
      empty($this->arguments) ? [] : [new CommandOverload(false, array_map([$this, 'createArgumentParam'], $this->arguments))],
      $subOverloads
    );
  }

  private function createArgumentParam(BaseArgument $arg): CommandParameter{
    $param = clone $arg->getNetworkParameterData();
    if ($param->enum) {
      (fn() => $this->enumName = "enum#" . spl_object_id($param->enum))->call($param->enum);
    }
    return $param;
  }

  public function execute(CommandSender $sender, string $label, array $args): bool{
    $this->currentSender = $sender;
    if (!$this->testPermission($sender))return false;
    // Handle subcommands
    if (!empty($args)) {
      if (isset($this->subCommands[$args[0]])) {
        $subData = $this->subCommands[$subName = array_shift($args)];
        if ($subData["permission"] && !$sender->hasPermission($subData["permission"])) {
          $this->sendPermissionError();
          return false;
        }
        if (!($sender instanceof Player && !$subData["console"])) {
          $sender->sendMessage("Use this command in-game");
          return false;
        }
        return $this->parseAndExecute($subData, $label, $args);
      }
    }
    // Handle main command arguments
    if (!empty($this->arguments)) {
      return $this->parseAndExecute([
        "arguments" => $this->arguments,
        "callback" => fn($sender_, $label_, $args_) => $this->onRun($sender_, $label_, $args_)
      ], $label, $args);
    }
    // No arguments required
    $this->onRun($sender, $label, []);
    return true;
  }

  private function parseAndExecute(array $data, string $label, array $args): bool{
    $sender = $this->currentSender;
    $parsed = [];
    $offset = 0;
    $required = array_sum(array_map(fn(BaseArgument $arg) => $arg->isOptional() ? 0 : 1, $data["arguments"]));
    foreach ($data["arguments"] as $pos => $arg) {
      if (count($values = array_slice($args, $offset, $span = $arg->getSpanLength())) < $span && !$arg->isOptional()) {
        $sender->sendMessage("§cMissing required argument: §7{$arg->getName()} (needs {$span} values)");
        return false;
      }
      if (count($values) < $span && $arg->isOptional())break;
      if (!$arg->canParse(($valueStr = implode(" ", $values)), $sender)) {
        $sender->sendMessage("§cInvalid value for: §7{$arg->getName()}");
        return false;
      }
      $parsed[$arg->getName()] = $arg->parse($valueStr, $sender);
      $offset += $span;
      if (!$arg->isOptional())$required--;
    }
    if ($offset < count($args)) {
      $sender->sendMessage("§cToo many arguments provided");
      return false;
    }
    if ($required > 0) {
      $sender->sendMessage("§cMissing required arguments");
      return false;
    }
    $data["callback"]($sender, $label, $parsed);
    return true;
  }

  private function sendPermissionError(): void{
    if (($msg = $this->getPermissionMessage()) === null) {
      $this->currentSender->sendMessage("§c" . $this->currentSender->getServer()->getLanguage()->translateString("%commands.generic.permission"));
    } elseif (!empty($msg)) {
      $this->currentSender->sendMessage(str_replace("<permission>", $this->getPermission(), $msg));
    }
  }

  abstract public function onRun(CommandSender $sender, string $aliasUsed, array $args): void;

  private function sendUsage(): void{
    $this->currentSender?->sendMessage("Usage: " . $this->getUsage());
  }

  public function getUsageMessage(): string{
		return $this->getUsage();
	}
}