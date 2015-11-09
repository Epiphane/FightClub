<?

/*
 * FightController class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Controller;

use \Exception;
use \Fight\Model\FightUserModel;
use \Fight\Model\FightModel;
use \Fight\Model\FightItemModel;
use \Fight\Model\FightActionModel;
use \Fight\Model\FightPrefsModel;
use \Fight\Controller\FightActionController;
use \Fight\Controller\FightUserController;
use \Fight\Controller\FightReactionController;
use \Fight\Controller\FightPrefsController;
use \Fight\Attachment\FightMessage;
use \Fight\Attachment\FightInfoMessage;

class FightController
{
   public static $RESERVED = ["help", "fight", "use", "equip", "item"];

   public static function fight_($argc, $argv, $user, $fight, $params) {
      if ($fight) {
         return new FightMessage("danger", "You're already in a fight! Type `status` to see how you're doing");
      }

      if ($argc !== 2 || in_array($argv[1], self::$RESERVED)) {
         return new FightInfoMessage([
            "Usage: `fight @XXX | fight monster`",
            "Type `fight help` for more commands"
         ]);
      }

      if ($argv[1] === "monster") {
         $opponent = FightAIController::getRandomMonster($user);

         $opponent->save();
      }
      else {
         $opponent = FightUserController::findUserByTag($user->alias->team_id, $argv[1]);
         if (!$opponent) {
            return new FightMessage("danger", "Sorry, `" . $argv[1] . "` is not recognized as a name");
         }
      }

      $otherExisting = FightModel::findOneWhere([
         "user_id" => $opponent->user_id,
         "channel_id" => $params["channel_id"],
         "status" => "progress"
      ]);

      if ($otherExisting) {
         return new FightMessage("danger", "Sorry, " . $opponent->tag() . " is already in a fight. Maybe take this somewhere else?");
      }

      $INITIAL_HEALTH_1 = 100;
      $INITIAL_HEALTH_2 = 100;

      if (!$user->weapon) {
         $INITIAL_HEALTH_2 = 35;
      }
      if (!$opponent->weapon) {
         $INITIAL_HEALTH_1 = 35;
      }

      $leveldiff = $opponent->level - $user->level;
      if ($leveldiff >= 0) {
         $INITIAL_HEALTH_1 += $leveldiff * 3;
      }
      else {
         $INITIAL_HEALTH_2 += $leveldiff * 3;
      }

      $fightParams = [
         "user_id" => $user->user_id,
         "channel_id" => $params["channel_id"],
         "status" => "progress",
         "health" => $INITIAL_HEALTH_1
      ];

      // Build fight 1
      $fight1 = FightModel::build($fightParams);
      if (!$fight1->save()) {
         throw new Exception("Server error. Code: 1");
      }
   
      // Build opponent's fight
      $fightParams["fight_id"] = $fight1->fight_id;
      $fightParams["user_id"] = $opponent->user_id;
      $fightParams["health"] = $INITIAL_HEALTH_2;
      $fight2 = FightModel::build($fightParams);
      if (!$fight2->save()) {
         throw new Exception("Server error. Code: 2");
      }

      // Register the action
      FightActionController::registerAction($user, $fight1->fight_id, $user->tag() . " challenges " . $opponent->tag() . " to a fight!");

      // If it's a monster (or slackbot) they get to go first
      if ($opponent->AI) {
         $computerMove = FightAIController::computerMove($user, $fight1, $opponent, $fight2);

         if (!is_array($computerMove)) {
            $computerMove = [$computerMove];
         }

         foreach ($computerMove as $action) {
            FightActionController::registerAction($opponent, $fight2->fight_id, $action->toString());
         }

         array_unshift($computerMove, new FightMessage("warning", "A wild " . $opponent->tag() . " appeared!"));

         return $computerMove;
      }

      return new FightMessage("good", "Bright it on, " . $opponent->tag() . "!!");
   }

   public static function forefeit_($argc, $argv, $user, $fight, $params) {
      list($otherFight, $opponent) = self::getOpponent($fight);

      FightActionController::registerAction($user, $fight->fight_id, $user->tag() . " forefeits to " . $opponent->tag() . "!");

      return [
         new FightMessage("good", "You gave up! " . $opponent->tag() . " wins!"),
         self::registerVictory($opponent, $otherFight, $user, $fight)
      ];
   }

   public static function registerVictory($user, $fight, $opponent, $otherFight) {
      // Update experience
      // 100 + pow(x, 2.6)
      $experience = $user->experience + $opponent->level * 10;
      $level = $user->level;
      $levelUps = 0;

      $expNeeded = 40 + pow($level, 2.6);
      while ($experience >= $expNeeded) {
         $experience -= $expNeeded;
         $level ++;
         $levelUps ++;

         $expNeeded = 40 + pow($level, 2.6);
      }

      $user->update([
         "level" => $level,
         "experience" => $experience
      ]);

      if (!$fight     ->update(["status" => "win" ])) throw new Exception("Server error. Code: 3");
      if (!$otherFight->update(["status" => "lose"])) throw new Exception("Server error. Code: 4");
   
      if ($levelUps > 0) {
         return new FightMessage("good", $user->tag() . " leveled up! ". $user->tag() . " is now level " . $level);
      }
      else {
         return new FightMessage("good", $user->tag() . " now has " . $experience . " experience.");
      }
   }

   public static $COMMANDS = [
      "`fight @XXX` : Pick a fight with @XXX",
      "`forefeit` : Quit your current fight (counts as a loss)",
      "`status` : Get your health, your opponent's health, and other info about the fight",
      "`equip XXX` : Equip an item in your inventory",
      "`use XXX` : Use a move on an opponent"
   ];
   public static function help_() {
      return new FightInfoMessage(self::$COMMANDS);
   }

   public static function status_($argc, $argv, $user, $fight, $params) {
      if (!$argv[1]) {
         if ($fight) {
            list($otherFight, $opponent) = self::getOpponent($fight);

            return new FightInfoMessage([
               " ",
               "Status update for " . $user->tag() . " (level " . $user->level . ")",
               " - Health: " . $fight->health,
               "You are fighting " . $opponent->tag() . " (level " . $opponent->level . ")",
               " - Health: " . $otherFight->health,
               "",
               "Type `status help` for more status options"
            ]);
         }
         else {
            return new FightInfoMessage([
               "Status update for " . $user->tag() . " (level " . $user->level . ")",
               "Type `status help` for more status options"
            ]);
         }
      }
      elseif ($argv[1] === "help") {
         return new FightInfoMessage([
            "`status`: General stats",
            "`status help`: This Dialog",
            "`status moves`: Your moves",
            "`status items`: Your items"
         ]);
      }
      elseif ($argv[1] === "items" || $argv[1] === "moves") {
         $items = FightItemModel::findWhere([
            "user_id" => $user->user_id,
            "type" => substr($argv[1], 0, 4),
            "deleted" => 0
         ]);

         if ($items->size() === 0) {
            return new FightWarningMessage([
               "You have no " . $argv[1] . "!",
               "Type `craft` to create one now"
            ]);
         }
         else {
            $output = ["Your " . $argv[1] . ":"];

            foreach ($items->objects as $index => $item) {
               $str = "";
               if ($item->item_id === $user->weapon) $str = "`*`";
               elseif ($item->item_id === $user->armor) $str = "`0`";

               $output[] = $str . ($index + 1) . ". " . $item->name . " - " . $item->shortdesc();
            }

            return new FightInfoMessage($output);
         }
      }
      else {
         return new FightMessage("danger", "Command " . $argv[1] . " not found.");
      }
   }

   public static function ping_($argc, $argv, $user, $fight, $params) {
      $opponent = self::getOpponent($fight)[1];

      return new FightMessage("danger", "Come on, " . $opponent->tag() . "! Make a move!");
   }

   public static function use_($argc, $argv, $user, $fight, $params) {
      self::requireTurn($user, $fight);

      return FightActionController::useItem($user, $fight, implode(" ", array_slice($argv, 1)));
   }

   public static function settings_($argc, $argv, $user, $fight, $params) {
      return new FightMessage("warning", "Sorry, settings is not implemented yet.");
   }

   public static function reaction_($argc, $argv, $user, $fight, $params) {
      return new FightMessage("warning", "Sorry, reaction is not implemented yet.");
   }

   public static function item_($argc, $argv, $user, $fight, $params) {
      if ($argv[1] === "drop") {
         $itemName = implode(" ", array_slice($argv, 2));

         $item = FightItemModel::findOneWhere([
            "user_id" => $user->user_id,
            "name" => $itemName,
            "deleted" => 0
         ]);

         if ($item) {
            $item->update([ "deleted" => 1 ]);

            return new FightMessage("good", $itemName . " dropped! Bye Bye!");
         }
         else {
            return new FightMessage("warning", "Sorry, " . $itemName . " couldn't be found.");
         }
      }
      else {
         $itemName = implode(" ", array_slice($argv, 1));

         $item = FightItemModel::findOneWhere([
            "user_id" => $user->user_id,
            "name" => $itemName,
            "deleted" => 0
         ]);

         if ($item) {
            return new FightMessage("good", $item->desc());
         }
         else {
            return new FightMessage("warning", "Sorry, " . $itemName . " couldn't be found.");
         }
      }
   }

   public static function craft_($argc, $argv, $user, $fight, $params) {
      $argS = implode(" ", array_slice($argv, 1));
      if (!$fight) {
         $itemCount = FightItemModel::findWhere([ "user_id", $user->user_id ]);

         if ($itemCount->size() >= 10) {
            return new FightDangerMessage("Sorry, you may not have more than 10 items. Type `item drop XXX` to drop an old item");
         }

         return FightCraftController::startCrafting($user, $params["channel_id"], $argS);
      }
      else {
         list($otherFight, $opponent) = self::getOpponent($fight);

         return FightCraftController::craft($fight, $user, $otherFight, $opponent, $argS);
      }
   }

   public static function equip_($argc, $argv, $user, $fight, $params) {
      $itemName = implode(" ", array_slice($argv, 2));
      if ($argv[1] !== "weapon" && $argv[1] !== "armor") {
         return new FightInfoMessage("Usage: `equip (weapon|armor) " . $argv[1] . "`");
      }

      $item = FightItemController::getItem($user, $itemName);

      if ($item) {
         if ($fight) {
            self::requireTurn($user, $fight);
         }

         $user->update([
            $argv[1] => $item->item_id
         ]);

         if ($fight) {
            FightActionController::registerAction($user, $fight->fight_id, $user->tag() . "equipped `" . $itemName . "`!");

            return new FightMessage("good", "You equipped `" . $itemName . "`! (yes, it used your turn)");
         }
         else {
            return new FightMessage("good", "You equipped `" . $itemName . "`!");
         }
      }
      else {
         return new FightMessage("warning", "Sorry, you don't have an item named `" . $itemName . "`");
      }
   }

   /* ----------------- */
   /* Utility functions */
   /* ----------------- */

   public static function findFight($user, $channel_id) {
      return FightModel::findOneWhere([
         "user_id" => $user->user_id,
         "channel_id" => $channel_id,
         "status" => "progress"
      ]);
   }

   public static function getOpponent($fight) {
      self::requireFight($fight);

      $request = new \Fight\Data\Request();
      $request->Filter[] = new \Fight\Data\Filter("fight_id", $fight->fight_id);
      $request->Filter[] = new \Fight\Data\Filter("channel_id", $fight->channel_id);
      $request->Filter[] = new \Fight\Data\Filter("user_id", $fight->user_id, "!=");

      $otherFight = FightModel::findOne($request);

      if (!$otherFight) {
         throw new Exception("You are not in a fight!");
      }

      return [ $otherFight, FightUserModel::findOneWhere([ "user_id" => $otherFight->user_id ]) ];
   }

   public static function requireFight($fight) {
      if (!$fight) throw new Exception("You cannot do that unless you're in a fight!");
   }

   public static function requireTurn($user, $fight) {
      self::requireFight($fight);

      $request = new \Fight\Data\Request();
      $request->Sort[] = new \Fight\Data\Sort("action_id", "DESC");
      $request->Filter[] = new \Fight\Data\Filter("fight_id", $fight->fight_id);
      $request->Filter[] = new \Fight\Data\Filter("created_at", date('Y-m-d H:i:s', strtotime('-5 minutes')), ">=");
      $lastAction = FightActionModel::findOne($request);

      if ($lastAction->actor_id === $user->user_id) {
         throw new Exception("It's not your turn! (if your opponent does not go for 5 minutes, it will become your turn)");
      }
   }
}