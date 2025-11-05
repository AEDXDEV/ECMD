# ECMD
design your command like Commando lib but easier — the best choice for making cores with *dynamic enums* and *advanced subcommand control*.

## ✅ Features
- 💡 Clean *subcommand system* with permission & sender type constraints per subcommand (Player-only, Console-only, or Both).
- ⚙️ *Dynamic arguments* that update automatically (players, worlds…).
- 🔄 Built-in *command packet sync* using PocketMine’s new `AvailableCommandsPacketAssembler`.
- 🧩 *SoftEnum autocompletion* — dynamically refreshed every 5 seconds.

🧱 Fully compatible with *PocketMine-MP v5.36+* (new command packet architecture).

---

## 🛠️ Basic Usage

### 1) Basic Arguments (no subcommands)
```php
<?php

namespace Author\Plugin;

use pocketmine\command\CommandSender;
use AEDXDEV\ECMD\BaseCommand;
use AEDXDEV\ECMD\args\{
  RawStringArgument,
  IntegerArgument,
  BooleanArgument
};

class GreetCommand extends BaseCommand {
  protected function prepare(): void{
    // /greet <player:string> [times:int] [shout:bool]
    $this->registerArgument(0, new RawStringArgument("player"));
    $this->registerArgument(1, new IntegerArgument("times", true));
    $this->registerArgument(2, new BooleanArgument("shout", true));
  }

  public function onRun(CommandSender $sender, string $label, array $args): void{
    $targetName = $args["player"];
    $times = $args["times"] ?? 1;
    $shout = $args["shout"] ?? false;
    $msg = "Hello, {$targetName}!";
    if ($shout) {
      $msg = "§l" . strtoupper($msg) . "§r";
    }
    for ($i = 0; $i < max(1, $times); $i++) {
      $sender->sendMessage($msg);
    }
  }
}
```

---

### 2) Subcommands (permissions & constraints)
```php
<?php

namespace Author\Plugin;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use AEDXDEV\ECMD\BaseCommand;
use AEDXDEV\ECMD\args\{
  RawStringArgument,
  TextArgument,
  IntegerArgument,
  Vector3Argument
};

class ArenaCommand extends BaseCommand {
  protected function prepare(): void{
    // /arena join <player:string>
    $this->registerSubCommand(
      "join",
      [new RawStringArgument("player")],
      function(CommandSender $sender, array $args): void{
        $p = $sender->getServer()->getPlayerExact($args["player"]);
        $p?->sendMessage("§aYou joined the arena!");
      },
      "arena.join",
      BaseCommand::ALL_CONSTRAINT // console & player
    );

    // /arena setspawn <x y z>
    $this->registerSubCommand(
      "setspawn",
      [new Vector3Argument("position")],
      function(CommandSender $sender, array $args): void{
        if ($sender instanceof Player) {
          $sender->teleport($args["position"]);
          $sender->sendMessage("§aSpawn set & teleported.");
        }
      },
      "arena.setspawn",
      BaseCommand::IN_GAME_CONSTRAINT // player only
    );

    // /arena start [countdown:int]
    $this->registerSubCommand(
      "start",
      [new IntegerArgument("countdown", true)],
      function(CommandSender $sender, array $args): void{
        $cd = $args["countdown"] ?? 5;
        $sender->getServer()->broadcastMessage("§eArena starts in §6{$cd}§e...");
      },
      "arena.start",
      BaseCommand::CONSOLE_CONSTRAINT // console only
    );
  }

  public function onRun(CommandSender $sender, string $label, array $args): void{
    $sender->sendMessage("§7Usage: /arena <join|setspawn|start> ...");
  }
}
```

---

### 3) Custom Argument (StringEnumArgument)
```php
<?php

namespace Author\Plugin;

use pocketmine\command\CommandSender;
use AEDXDEV\ECMD\BaseCommand;
use AEDXDEV\ECMD\args\{
  StringEnumArgument,
  TextArgument
  };

class ColorArgument extends StringEnumArgument {
  // value => mapped result
  protected static array $VALUES = [
    "red"   => "§c",
    "green" => "§a",
    "blue"  => "§9",
    "gold"  => "§6",
  ];

  public function getTypeName(): string{
    return "color";
  }
}

class ColorSayCommand extends BaseCommand {
  protected function prepare(): void{
    // /colorsay <color:red|green|blue|gold> <text...>
    $this->registerArgument(0, new ColorArgument("color"));
    $this->registerArgument(1, new TextArgument("text"));
  }

  public function onRun(CommandSender $sender, string $label, array $args): void{
    $color = $args["color"]; // returns the mapped code like "§c"
    $text  = $args["text"];
    $sender->sendMessage($color . $text);
  }
}
```

---

### 4) Dynamic Argument (auto-updating enum)

> The values are updated automatically every 5 seconds by the BaseCommand (you don't need to set up the Scheduler yourself).

```php
<?php

namespace Author\Plugin;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use AEDXDEV\ECMD\BaseCommand;
use AEDXDEV\ECMD\args\StringEnumArgument;

class PlayerArgument extends StringEnumArgument {
  public function getTypeName(): string{
    return "string";
  }

  public function canParse(string $testString, CommandSender $sender): bool{ // Is valid value 
    return Server::getInstance()->getPlayerExact($testString) !== null;
  }

  public function getValue(string $string): mixed{ // Return the requested value when using $args["player"];
    return Server::getInstance()->getPlayerExact($string);
  }

  public static function tick(): void{
    // keys = what players see in autocomplete, values = what parse() returns
    $names = array_map(fn (Player $p) => $p->getName(), Server::getInstance()->getOnlinePlayers());
    static::$VALUES = array_combine($names, $names);
    // this will push the new list into the soft enum used by the client
    parent::tick();
  }
}

class TptoCommand extends BaseCommand {
  protected function prepare(): void{
    // /tpto <player:string>
    $this->registerArgument(0, new PlayerArgument("player"));
  }

  public function onRun(CommandSender $sender, string $label, array $args): void{
    if (!$sender instanceof Player) {
      $sender->sendMessage("§cPlayer only.");
      return;
    }
    $target = $sender->getServer()->getPlayerExact($args["player"]);
    if($target === null){ $sender->sendMessage("§cPlayer not found."); return; }
    $sender->teleport($target->getLocation());
    $sender->sendMessage("§aTeleported to §e" . $target->getName());
  }
}
```

---

## ⚙️ Dynamic Argument Support

*PlayerArgument* and *WorldArgument* update automatically every 5 seconds to reflect:

- Connected players

- Loaded worlds

All updates are handled internally through ECMD’s scheduler — no extra setup or manual calls to tick() required.

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
- Require specific permissions
- Include dynamic enums for autocomplete (players, worlds, etc.)
- Sync automatically via AvailableCommandsPacketAssembler

---

## ⚡ Automatic Enum Refresh System

All *StringEnumArgument* subclasses (like *PlayerArgument*, *WorldArgument*, etc.)
register themselves automatically when instantiated.

The ECMD scheduler:

Finds all dynamic enum classes

Calls *::tick()* for each one every 5 seconds

Re-syncs updated command data to all players seamlessly


✅ No need to manually register or manage your dynamic arguments — just extend *StringEnumArgument*.

---

## 📜 License
This project is licensed under the [GPL-3.0 License](LICENSE).

---

## YouTube
- [@AEDXDEV](https://youtube.com/@aedxdev?si=hGoo2eohlFlFbBNu)

## Discord
- aedxdev