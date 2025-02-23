# ECMD
design your command like Commando lib but easier (best choice for making cores)


# How to use

```php
<?php

namespace Author\Plugin;

use pocketmine\command\CommandSender;
use pocketmine\world\Position;

use AEDXDEV\ECMD\BaseCommand;
use AEDXDEV\ECMD\args{
  StringArgument,
  BoolArgument,
  FloatArgument
};

class TeleportCommand extends BaseCommand {
  protected function prepare(): void {
    $this->registerArgument(0, new StringArgument("player"));
    $this->registerArgument(1, new BoolArgument("message", true));
    
    $this->registerSubCommand("teleport_to_position", [
      new FloatArgument("x"),
      new FloatArgument("y"),
      new FloatArgument("z")
    ], function (CommandSender $sender, array $args) {
      $sender->sendMessage("§eTeleport to position " . implode(" ", [$args["x"], $args["y"], $args["z"]]));
      $pos = new Position($args["x"], $args["y"], $args["z"]);
      $sender->teleport($pos);
    }, "mycommand.teleport", false);
  }
  
  public function onRun(CommandSender $sender, string $label, array $args): void{
    if (!$sender instanceof Player) {
      $sender->sendMessage("Use this command in game");
      return;
    }
    $target = Server::getInstance()->getPlayerByPerfix($args["player"]);
    if ($target !== null) {
      $sender->teleport($target->getPosition());
      if ($args["message"]) {
        $target->sendMessage($sender->getName() . " §eis teleported to you");
      }
    } else {
      $sender->sendMessage("§cPlayer not found");
    }
  }
  
  public function onTeleport(CommandSender $sender, array $args): void {
    $pos = new Position($args["x"], $args["y"], $args["z"]);
    $sender->teleport($pos);
  }
}
```