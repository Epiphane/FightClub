<?

/*
 * FightActionController class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Controller;

use \Fight\Model\FightUserModel;
use \Fight\Model\FightModel;
use \Fight\Model\FightItemModel;
use \Fight\Model\FightActionModel;
use \Fight\Controller\FightController;
use \Fight\Controller\FightItemController;
use \Fight\Controller\FightPrefsController;
use \Fight\Attachment\FightReaction;
use \Fight\Attachment\FightMessage;
use \Fight\Attachment\FightInfoMessage;
use \Fight\Attachment\FightWarningMessage;
use \Fight\Attachment\FightGoodMessage;
use \Fight\Attachment\FightDangerMessage;

class FightActionController
{
   public static function useItem($user, $fight, $action) {
      if (strtolower($action) === "taunt") {
         FightActionController::registerAction($user, $fight->fight_id, $user->tag() . " uses Taunt!");

         return [
            new FightMessage("good", [$user->tag() . " uses Taunt!", "What an insult!"]),
            new FightReaction($fight->channel_id)
         ];
      }

      $move = FightItemController::getMove($user, $action);

      if (!$move) {
         $result = [$action . " not available! Options:"];

         $moves = FightItemModel::findWhere([
            "user_id" => $user->user_id,
            "type" => "move",
            "deleted" => 0
         ]);

         foreach ($moves->objects as $move) {
            $result[] = "`" . $move->name . "`";
         }

         return new FightMessage("danger", $result);
      }
      else {
         $action = FightItemController::useMove($move, $user, $fight);
         if (!is_array($action)) {
            $action = [$action];
         }

         $action[] = new FightReaction($fight->channel_id);

         return $action;
      }
   }

   public static function equipItem($user, $type, $itemName) {
      $item = FightItemController::getItem($user, $itemName);

      if ($item) {
         $user->update([
            $type => $item->item_id
         ]);

         return new FightGoodMessage("You equipped `" . $itemName . "`!");
      }
      else {
         return new FightWarningMessage("Sorry, you don't have an item named `" . $itemName . "`");
      }
   }

   public static function _equip($fight, $user, $otherFight, $opponent, $action, $command) {
      $itemName = implode(" ", array_slice($command, 2));
      if ($command[1] !== "weapon" && $command[1] !== "armor") {
         return new FightInfoMessage("Usage: `equip (weapon|armor) " . $itemName . "`");
      }

      $result = FightActionController::equipItem($user, $command[1], $itemName);
      $item = FightItemController::getItem($user, $itemName);

      if ($item) {
         $user->update([
            $command[1] => $item->item_id
         ]);

         FightActionController::registerAction($user, $fight->fight_id, $user . "equipped `" . $itemName . "`!");
         return new FightGoodMessage("You equipped `" . $itemName . "`! (yes, it used your turn)");
      }
      else {
         return new FightWarningMessage("Sorry, you don't have an item named `" . $itemName . "`");
      }
   }

   public static function _craft($fight, $user, $otherFight, $opponent, $action, $command) {
      return FightCraftController::craft($fight, $user, $otherFight, $opponent, $action, $command);
   }

   public static function registerAction($user, $fight_id, $description) {
      $action = FightActionModel::build([
         "fight_id" => $fight_id,
         "description" => $description,
         "actor_id" => $user->user_id
      ]);

      return $action->save();
   }
}