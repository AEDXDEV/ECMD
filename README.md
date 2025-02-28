# ECMD
design your command like Commando lib but easier (best choice for making cores)

## 🛠️ Basic Usage
```php
<?php

namespace Author\Plugin;

use pocketmine\command\CommandSender;
use pocketmine\world\Position;

use AEDXDEV\ECMD\BaseCommand;
use AEDXDEV\ECMD\args{
  StringArgument,
  TextArgument,
  BoolArgument,
  Vector3Argument
};

class MyCommand extends BaseCommand {

  protected function prepare(): void{
    // Register main command arguments
    $this->registerArgument(0, new StringArgument("player"));
    $this->registerArgument(1, new BoolArgument("say_hello", true));
    
    // Register sub-command
    $this->registerSubCommand(
      "tp",
      [
        new Vector3Argument("position")
      ],
      function(CommandSender $sender, array $args) {
        $sender->teleport($args["position"]);
      },
      "command.tp",
      false // Player-only
    );
    $this->registerSubCommand(
      "msg",
      [
        new StringArgument("player"),
        new TextArgument("message")
      ],
      function(CommandSender $sender, array $args) {
        $target = $this->getServer()->getPlayerExact($args["player"]);
        $target?->sendMessage($sender->getName() . " §eSent to you a message'§7" . $args["message"] . "§e'");
      },
      "command.msg",
      true // Player, Console
    );
  }

  public function onRun(CommandSender $sender, string $label, array $args): void {
    // Main command logic
    $target = $this->getServer()->getPlayerByPrefix($args["player"]);
    if($args["say_hello"]) {
      $target?->sendMessage($sender->getNane() . " §bwelcomes you");
    }
  }
}
```
---


## 📚 Argument Types
| Type             | Class                      | Example           |
|---------------------|-------------------------|-------------------|
| String              | `StringArgument`        | "PocketMine"      |
| Text              | `TextArgument`        | "Hello world!"      |
| Integer             | `IntegerArgument`       | 42                |
| Float               | `FloatArgument`         | 3.14              |
| Boolean             | `BooleanArgument`       | true/false        |
| Position            | `Vector3Argument`       | 100 64 200        |
| Block Position      | `BlockPositionArgument` | ~ ~ ~1            |
---


## 📜 License
This project is licensed under the [GPL-3.0 License](LICENSE).

---