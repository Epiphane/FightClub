<?

/*
 * FightController class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Controller;

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
use \Fight\Attachment\FightWarningMessage;
use \Fight\Attachment\FightGoodMessage;
use \Fight\Attachment\FightDangerMessage;

class FightController
{
   public static $RESERVED = ["help", "fight", "use", "equip", "item"];

   public static function fight_($argc, $argv, $user, $fight, $params) {
      if ($fight) {
         return new FightMessage("danger", "You're already in a fight! Type `status` to see how you're doing");
      }

      if ($argv !== 2 || in_array($argv[1], self::$RESERVED)) {
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
         $opponent = FightUserController::findUserByTag($user->team_id, $argv1[1]);
         if (!$opponent) {
            return new FightDangerMessage("danger", "Sorry, `" . $argv1[1] . "` is not recognized as a name");
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

      $INITIAL_HEALTH = 100;

      $fightParams = [
         "user_id" => $user->user_id,
         "channel_id" => $params["channel_id"],
         "status" => "progress",
         "health" => $INITIAL_HEALTH
      ];

      // Build fight 1
      $fight1 = FightModel::build($fightParams);
      if (!$fight1->save()) throw new \Exception("Server error. Code: 1");
   
      // Build opponent's fight
      $fightParams["fight_id"] = $fight1->fight_id;
      $fight2 = FightModel::build($fightParams);
      if (!$fight2->save()) throw new \Exception("Server error. Code: 2");

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

   public static function settings_($argc, argv, $user, $fight, $params) {
      return new FightMessage("warning", "Sorry, settings is not implemented yet.");
   }

   public static function reaction_($argc, argv, $user, $fight, $params) {
      return new FightMessage("warning", "Sorry, reaction is not implemented yet.");
   }

   public static function item_($argc, argv, $user, $fight, $params) {
      return new FightMessage("warning", "Sorry, item is not implemented yet.");
   }

   public static function craft_($argc, argv, $user, $fight, $params) {
      return new FightMessage("warning", "Sorry, craft is not implemented yet.");
   }

   public static function status_($argc, argv, $user, $fight, $params) {
      return new FightMessage("warning", "Sorry, status is not implemented yet.");
   }

   public static function equip_($argc, argv, $user, $fight, $params) {
      return new FightMessage("warning", "Sorry, equip is not implemented yet.");
   }

   public static function ping_($argc, argv, $user, $fight, $params) {
      return new FightMessage("warning", "Sorry, ping is not implemented yet.");
   }

   public static function use_($argc, argv, $user, $fight, $params) {
      return new FightMessage("warning", "Sorry, use is not implemented yet.");
   }

         // if ($command === "fight ping") {
         //    return new FightDangerMessage("Come on, " . $opponent->tag() . "! Make a move!");
         // }

         // return self::runFight($existing, $user, $otherFight, $opponent, $trigger, $cmdParts);

   public static function findFight($user, $channel_id) {
      return FightModel::findOneWhere([
         "user_id" => $user->user_id,
         "channel_id" => $channel_id,
         "status" => "progress"
      ]);
   }

   public static function getOpponent($fight) {
      $request = new \Data\Request();
      $request->Filter[] = new \Data\Filter("fight_id", $fight->fight_id);
      $request->Filter[] = new \Data\Filter("channel_id", $fight->channel_id);
      $request->Filter[] = new \Data\Filter("user_id", $fight->user_id, "!=");

      $otherFight = FightModel::findOne($request);
      return [
         "fight" => $otherFight,
         "user" => FightUserModel::findOneWhere([ "user_id" => $otherFight->user_id ])
      ];
   }

   public static function _fight($user, $channel_id, $trigger, $command) {
      if ($command === "fight help") {
         return self::help_();
      }

      $settings = FightPrefsController::findById($channel_id);

      $existing = FightModel::findOneWhere([
         "user_id" => $user->user_id,
         "channel_id" => $channel_id,
         "status" => "progress"
      ]);
      $cmdParts = explode(" ", $command);

      if ($cmdParts[1] === "reaction") {
         return FightReactionController::addReaction($user, $cmdParts[2], $cmdParts[3]);
      }
      elseif ($cmdParts[1] === "settings") {
         return FightPrefsController::updateSettings($settings, array_slice($cmdParts, 2));
      }

      if (!$existing) {
         if ($trigger === "fight") {
            if (count($cmdParts) === 2 && $cmdParts[1] !== "help") {
               if ($cmdParts[1] === "monster") {
                  $opponent = FightAIController::getRandomMonster($user);

                  $opponent->save();
               }
               else {
                  $opponent = FightController::findUserByTag($user->team_id, $cmdParts[1]);
                  if (!$opponent) {
                     return new FightDangerMessage("Sorry, `" . $cmdParts[1] . "` is not recognized as a name");
                  }
               }

               $otherExisting = FightModel::findOneWhere([
                  "user_id" => $opponent->user_id,
                  "channel_id" => $channel_id,
                  "status" => "progress"
               ]);

               if ($otherExisting) {
                  return new FightDangerMessage("Sorry, " . $opponent->tag() . " is already in a fight. Maybe take this somewhere else?");
               }

               $INITIAL_HEALTH = 100;

               $fight1 = FightModel::build([
                  "user_id" => $user->user_id,
                  "channel_id" => $channel_id,
                  "status" => "progress",
                  "health" => $INITIAL_HEALTH
               ]);
               if (!$fight1->save()) return SERVER_ERR . "1";
            
               $fight2 = FightModel::build([
                  "fight_id" => $fight1->fight_id,
                  "user_id" => $opponent->user_id,
                  "channel_id" => $channel_id,
                  "status" => "progress",
                  "health" => $INITIAL_HEALTH
               ]);
               if (!$fight2->save()) return SERVER_ERR . "2";

               FightActionController::registerAction($user, $fight1->fight_id, $user->tag() . " challenges " . $opponent->tag() . " to a fight!");

               if ($opponent->AI) {
                  $computerMove = FightAIController::computerMove($user, $fight1, $opponent, $fight2);

                  if (!is_array($computerMove)) {
                     $computerMove = [$computerMove];
                  }

                  foreach ($computerMove as $action) {
                     FightActionController::registerAction($opponent, $fight2->fight_id, $action->toString());
                  }

                  array_unshift($computerMove, new FightWarningMessage("A wild " . $opponent->tag() . " appeared!"));

                  return $computerMove;
               }

               return new FightGoodMessage("Bright it on, " . $opponent->tag() . "!!");
            }
            else {
               return new FightInfoMessage("Usage: `fight @XXX | fight monster`\nType `fight help` for more commands");
            }
         }
         else if ($trigger === "item") {
            if ($cmdParts[1] === "drop") {
               $itemName = implode(" ", array_slice($cmdParts, 2));

               $item = FightItemModel::findOneWhere([
                  "user_id" => $user->user_id,
                  "name" => $itemName,
                  "deleted" => 0
               ]);

               if ($item) {
                  $item->update([ "deleted" => 1 ]);

                  return new FightGoodMessage($itemName . " dropped! Bye Bye!");
               }
               else {
                  return new FightWarningMessage("Sorry, " . $itemName . " couldn't be found.");
               }
            }
            else {
               $itemName = implode(" ", array_slice($cmdParts, 1));

               $item = FightItemModel::findOneWhere([
                  "user_id" => $user->user_id,
                  "name" => $itemName,
                  "deleted" => 0
               ]);

               if ($item) {
                  return new FightGoodMessage($item->desc());
               }
               else {
                  return new FightWarningMessage("Sorry, " . $itemName . " couldn't be found.");
               }
            }
         }
         else if ($trigger === "craft") {
            $itemCount = FightItemModel::findWhere([ "user_id", $user->user_id ]);

            if ($itemCount->size() >= 10) {
               return new FightDangerMessage("Sorry, you may not have more than 10 items. Type `item drop XXX` to drop an old item");
            }

            return FightCraftController::startCrafting($user, $channel_id, array_slice($cmdParts, 1));
         }
         else if ($trigger === "status") {
            return FightController::status($user, $cmdParts[1]);
         }
         else if ($trigger === "equip") {
            $itemName = implode(" ", array_slice($cmdParts, 2));
            if ($cmdParts[1] !== "weapon" && $cmdParts[1] !== "armor") {
               return new FightInfoMessage("Usage: `equip (weapon|armor) " . $itemName . "`");
            }

            return FightActionController::equipItem($user, $cmdParts[1], $itemName);
         }
         else {
            return new FightDangerMessage("Sorry, you're not fighting anyone right now. Type `fight XXX` to start a fight!");
         }
      }
      else {
         $request = new \Data\Request();
         $request->Filter[] = new \Data\Filter("fight_id", $existing->fight_id);
         $request->Filter[] = new \Data\Filter("channel_id", $channel_id);
         $request->Filter[] = new \Data\Filter("user_id", $existing->user_id, "!=");

         $otherFight = FightModel::findOne($request);
         $opponent = FightUserModel::findOneWhere([ "user_id" => $otherFight->user_id ]);

         if ($command === "fight ping") {
            return new FightDangerMessage("Come on, " . $opponent->tag() . "! Make a move!");
         }

         return self::runFight($existing, $user, $otherFight, $opponent, $trigger, $cmdParts);
      }
   }

   public static function runFight($fight, $user, $otherFight, $opponent, $action, $command) {
      $request = new \Data\Request();
      $request->Sort[] = new \Data\Sort("action_id", "DESC");
      $request->Filter[] = new \Data\Filter("fight_id", $fight->fight_id);
      $request->Filter[] = new \Data\Filter("created_at", date('Y-m-d H:i:s', strtotime('-5 minutes')), ">=");
      $lastAction = FightActionModel::findOne($request);

      if (!in_array($action, ["status", "forefeit", "craft"]) && $lastAction->actor_id === $user->user_id && $opponent->name !== "USLACKBOT") {
         return new FightDangerMessage("It's not your turn! (if your opponent does not go for 5 minutes, it will become your turn)");
      }

      $action = "_" . $action;

      return FightActionController::$action($fight, $user, $otherFight, $opponent, $action, $command);
   }

   public static function status($user, $section = "") {
      if (!$section) {
         return new FightMessage([
            "Status update for " . $user->tag() . " (level " . $user->level . ")",
            "Type `status help` for more status options"
         ]);
      }
      elseif ($section === "help") {
         return new FightInfoMessage([
            "`status`: General stats",
            "`status help`: This Dialog",
            "`status moves`: Your moves",
            "`status items`: Your items"
         ]);
      }
      elseif ($section === "items" || $section === "moves") {
         $items = FightItemModel::findWhere([
            "user_id" => $user->user_id,
            "type" => substr($section, 0, 4),
            "deleted" => 0
         ]);

         if ($items->size() === 0) {
            return new FightWarningMessage([
               "You have no " . $section . "!",
               "Type `craft` to create one now"
            ]);
         }
         else {
            $output = ["Your " . $section . ":"];

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
         return new FightDangerMessage("Command " . $section . " not found.");
      }
   }
}