<?php

declare(strict_types=1);

namespace AEDXDEV\ECMD;

use AEDXDEV\ECMD\args\{
  BaseArgument,
  PlayerArgument,
  WorldArgument,
  StringEnumArgument
};

use muqsit\simplepackethandler\SimplePacketHandler;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\serializer\AvailableCommandsPacketAssembler;
use pocketmine\network\mcpe\protocol\serializer\AvailableCommandsPacketDisassembler;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

final class PacketHooker{

  private static bool $registered = false;
  private static bool $intercepting = false;

  public static function register(Plugin $plugin) : void{
    if (self::$registered)return;
    $interceptor = SimplePacketHandler::createInterceptor($plugin, EventPriority::NORMAL, false);
    $interceptor->interceptOutgoing(function(AvailableCommandsPacket $pk, NetworkSession $session): bool{
      if (self::$intercepting)return true;
      if (($player = $session->getPlayer()) === null)return true;
      if (class_exists(AvailableCommandsPacketAssembler::class)) {
        // PMMP 5.36+
        $disassembled = AvailableCommandsPacketDisassembler::disassemble($pk);
        $list = $disassembled->commandData;
        foreach ($list as $cmdData) {
          if (!($cmd = Server::getInstance()->getCommandMap()->getCommand($cmdData->getName())) instanceof BaseCommand)continue;
            $cmdData->overloads = $cmd->generateOverloads($player);
        }
        self::$intercepting = true;
        $session->sendDataPacket(AvailableCommandsPacketAssembler::assemble($list, [], $disassembled->softEnums ?? []));
        self::$intercepting = false;
      } else {
        // Legacy PMMP
        foreach ($pk->commandData as $cmdData) {
          if (!($cmd = Server::getInstance()->getCommandMap()->getCommand($cmdData->getName())) instanceof BaseCommand)continue;
            $pk->commandData[$cmdData->getName()]->overloads = $cmd->generateOverloads($player);
        }
        $pk->softEnums = [];
        self::$intercepting = true;
        $session->sendDataPacket($pk);
        self::$intercepting = false;
      }
      return false;
    });
    $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void{
      // update dynamic enums
      foreach (StringEnumArgument::getDynamicClasses() as $class)$class::tick();
      //PlayerArgument::tick();
      //WorldArgument::tick();
      foreach (Server::getInstance()->getOnlinePlayers() as $p)$p->getNetworkSession()->syncAvailableCommands();
    }), 20 * 5);
    self::$registered = true;
  }
}