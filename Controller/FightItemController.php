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
use \Fight\Controller\FightActionController;
use \Fight\Controller\FightAIController;
use \Fight\Controller\FightController;
use \Fight\Attachment\FightMessage;
use \Fight\Attachment\FightInfoMessage;
use \Fight\Attachment\FightWarningMessage;
use \Fight\Attachment\FightGoodMessage;
use \Fight\Attachment\FightDangerMessage;

class FightItemController
{
   public static function getItem($user, $name) {
      return FightItemModel::findOneWhere([
         "name" => $name,
         "user_id" => $user->user_id
      ]);
   }

   public static function getMove($user, $name) {
      return FightItemModel::findOneWhere([
         "name" => $name,
         "type" => "move",
         "user_id" => $user->user_id
      ]);
   }

   public static $elements = ["water", "earth", "iron"];//, "wind", "electric", "fighting"];
   public static $superEffective = [
      "water" => ["iron"],
      "earth" => ["water", "electric"],
      "iron" => ["earth", "fighting"],
      "wind" => ["electric", "wind"],
      "electric" => ["water", "fighting"],
      "fighting" => ["wind", "earth"],
      "none" => []
   ];
   public static $notEffective = [
      "water" => ["earth", "electric"],
      "earth" => ["iron", "fighting"],
      "iron" => ["water"],
      "wind" => ["fighting"],
      "electric" => ["wind", "earth"],
      "fighting" => ["electric", "iron"],
      "none" => []
   ];

   public static function useMove($move, $user, $fight, $opponent, $otherFight) {
      if (!$opponent) {
         list($otherFight, $opponent) = FightController::getOpponent($fight);
      }

      $message = [$user->tag() . " uses " . $move->name];

      $weapon = FightItemModel::findById($user->weapon);
      $armor = FightItemModel::findById($opponent->armor);

      if ($weapon) {
         $message[0] .= " with their " . $weapon->name;
      }
   
      $message[0] .= "!";

      $critical = 0;
      $effective = 0;

      $moveAlignment = $move->stats["alignment"] ?: "none";
      $weaponAlignment = $weapon ? $weapon->stats["alignment"] : "none";
      $armorAlignment = $armor ? $armor->stats["alignment"] : "none";

      if (!$weaponAlignment) $weaponAlignment = "none";
      if (!$armorAlignment) $armorAlignment = "none";

      // Super and not effective
      if (in_array($armorAlignment, self::$superEffective[$moveAlignment])) {
         $move->stats["physical"] *= 1.5;
         $move->stats["elemental"] *= 1.5;

         $effective += 1;
      }
      if (in_array($armorAlignment, self::$notEffective[$moveAlignment])) {
         $move->stats["physical"] /= 2;
         $move->stats["elemental"] /= 2;

         $effective -= 1;
      }

      // Critical stuff
      if (rand(0, 100) <= 5 + $move->stats["luck"]) {
         $move->stats["physical"] *= 1.5;
         $move->stats["elemental"] *= 1.5;

         $critical += 1;
      }

      // Compute damage
      $physicalDamage = $move->stats["physical"];
      $elementalDamage = [
         $moveAlignment => $move->stats["elemental"]
      ];

      // Weapon stuff
      if ($weapon) {
         if (rand(0, 100) <= 5 + $weapon->stats["luck"]) {
            $weapon->stats["physical"] *= 1.5;
            $weapon->stats["elemental"] *= 1.5;

            $critical += 1;
         }

         if (in_array($armorAlignment, self::$superEffective[$weaponAlignment])) {
            $weapon->stats["physical"] *= 1.5;
            $weapon->stats["elemental"] *= 1.5;

            $effective += 1;
         }
         if (in_array($armorAlignment, self::$notEffective[$weaponAlignment])) {
            $weapon->stats["physical"] /= 2;
            $weapon->stats["elemental"] /= 2;

            $effective -= 1;
         }

         $physicalDamage += $weapon->stats["physical"];
         $elementalDamage[$weaponAlignment] += $weapon->stats["elemental"];
      }

      // Physical attack
      $damage = max(2, $physicalDamage * (1 - $armor->stats["physical"] / 100) - $armor->stats["defense"]);
      foreach ($elementalDamage as $element => $dmg) {
         if ($armorAlignment === $element || in_array($armorAlignment, self::$notEffective[$element])) {
            $damage += max(2, $dmg * (1 - $armor->stats["elemental"] / 100));
         }
      }

      $damage = round($damage);

      if ($critical === 1) {
         $message[] = "Critical hit!";
      }
      elseif ($critical === 2) {
         $message[] = "HYPERCritical hit!!!!";
      }

      if ($effective === -2) {
         $message[] = "But it hardly does anything!";
      }
      elseif ($effective === -1) {
         $message[] = "It wasn't very effective...";
      }
      elseif ($effective === 1) {
         $message[] = "It's super effective!";
      }
      elseif ($effective === 2) {
         $message[] = "OMIGOD YOU KNOCKED HIS SOCKS OFF SO EFFECTIVE!!";
      }

      $color = null;
      if ($move->stats["alignment"] === "iron") {
         $color = "#FF0000";
      }
      if ($move->stats["alignment"] === "earth") {
         $color = "#00FF00";
      }
      if ($move->stats["alignment"] === "water") {
         $color = "#0000FF";
      }

      // Fight result

      $newHealth = $otherFight->health - $damage;
      if ($newHealth > 0) {
         $otherFight->update([
            "health" => $newHealth
         ]);

         $message[] = "(" . $damage . " damage) " . $opponent->tag() . " now has " . $newHealth . " health.";
      }
      else {
         $levelUp = FightController::registerVictory($user, $fight, $opponent, $otherFight);
         $otherFight->update([ "health" => $newHealth ]);

         $message[] = "(" . $damage . " damage) " . $opponent->tag() . " fainted!!";

         if ($opponent->AI && $item = FightAIController::dropitem($opponent)) {
            $item->update([ "user_id" => $user->user_id ]);

            return [
               new FightMessage($color, $message),
               new FightMessage("good", "You picked up: " . $item->name . "!")
            ];
         }

         return [
            new FightMessage($color, $message),
            $levelUp
         ];
      }

      $result = new FightMessage($color, $message);
      FightActionController::registerAction($user, $fight->fight_id, $result->toString());

      if ($opponent->AI) {
         $opponentMove = FightAIController::computerMove($user, $fight, $opponent, $otherFight);
         if (!is_array($opponentMove)) {
            $opponentMove = [$opponentMove];
         }

         array_unshift($opponentMove, $result);

         return $opponentMove;
      }

      return $result;
   }
}