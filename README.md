# ECMD
design your command like Commando lib but easier — the best choice for making cores with dynamic enums and advanced subcommand control.

## ✅ Features
- Clean subcommand system with permission & sender type constraints.
- Support for dynamic argument values (players, items, worlds...).
- Automatic command packet syncing using task-based or packet intercept method.
- Enum autocompletion via `getEnumValues()` dynamically updated every 5 seconds.

---

## 🛠️ Basic Usage
```php
<?php

namespace Author\Plugin;

use pocketmine\command\CommandSender;
use pocketmine\world\Position;

use AEDXDEV\ECMD\BaseCommand;
use AEDXDEV\ECMD\args\{
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
      function(CommandSender $sender, array $args): void{
        $sender->teleport($args["position"]);
      },
      "command.tp",
      BaseCommand::IN_GAME_CONSTRAINT // Only Player
    );

    $this->registerSubCommand(
      "msg",
      [
        new StringArgument("player"),
        new TextArgument("message")
      ],
      function(CommandSender $sender, array $args): void{
        $target = $sender->getServer()->getPlayerExact($args["player"]);
        $target?->sendMessage($sender->getName() . " §eSent to you a message: §7" . $args["message"] . "§e");
      },
      "command.msg",
      BaseCommand::ALL_CONSTRAINT // Both Player & Console
    );
    $this->registerSubCommand(
      "broadcast",
      [
        new TextArgument("message")
      ],
      function(CommandSender $sender, array $args): void{
        foreach ($sender->getServer()->getOnlinePlayers() as $p) {
          $p->sendMessage($args["message"]);
        }
      },
      "command.broadcast",
      BaseCommand::CONSOLE_CONSTRAINT // Only Console
    );
  }

  public function onRun(CommandSender $sender, string $label, array $args): void{
    $target = $this->getServer()->getPlayerByPrefix($args["player"]);
    if($args["say_hello"]) {
      $target?->sendMessage($sender->getName() . " §bwelcomes you");
    }
  }
}
```

---

## ⚙️ Dynamic Argument Support

- `PlayerArgument`, `ItemArgument`, and `WorldArgument` now support dynamic updating every 5 seconds using:

---

## Examples
<p align="center">
  <img src="https://raw.githubusercontent.com/AEDXDEV/ECMD/main/example/Example.png" height="256" width="256">
</p>

---

## 📚 Argument Types
| Type             | Class                      | Example           |
|------------------|----------------------------|-------------------|
| String            | `StringArgument`          | "PocketMine"      |
| Text              | `TextArgument`            | "Hello world!"    |
| Integer           | `IntegerArgument`         | 42                |
| Float             | `FloatArgument`           | 3.14              |
| Boolean           | `BooleanArgument`         | true/false        |
| Position          | `Vector3Argument`         | 100 64 200        |
| Block Position    | `BlockPositionArgument`   | ~ ~ ~1            |
| Player            | `PlayerArgument`          | Notch             |
| Item              | `ItemArgument`            | diamond_sword     |
| World             | `WorldArgument`           | world             |

---

## 🧠 Smart Subcommands

Each subcommand can:
- Have its own arguments
- Be restricted to console/player and both
- Require a specific permission
- Update dynamically for autocompletion

---

## 📜 License
This project is licensed under the [GPL-3.0 License](LICENSE).

---

## YouTube
- [@AEDXDEV](https://youtube.com/@aedxdev?si=hGoo2eohlFlFbBNu)

## Discord
- aedxdev