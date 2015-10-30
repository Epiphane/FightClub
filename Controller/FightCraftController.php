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
use \Fight\Controller\FightActionController;
use \Fight\Attachment\FightInfoMessage;
use \Fight\Attachment\FightWarningMessage;
use \Fight\Attachment\FightGoodMessage;
use \Fight\Attachment\FightDangerMessage;

class FightCraftController
{
   public static function startCrafting($user, $channel_id, $args) {
      $name = implode(" ", $args);

      if ($name) {
         $item = FightItemModel::build([
            "name" => $name,
            "user_id" => $user->user_id,
            "stats" => [ "category" => false ]
         ]);

         $item->save();

         // Create "fight"
         $fight1 = FightModel::build([
            "user_id" => $user->user_id,
            "channel_id" => $channel_id,
            "status" => "progress",
            "health" => $item->item_id
         ]);
         if (!$fight1->save()) return SERVER_ERR . "1";
      
         $fight2 = FightModel::build([
            "fight_id" => $fight1->fight_id,
            "user_id" => 1, // UCRAFTBOT
            "channel_id" => $channel_id,
            "status" => "progress",
            "health" => $item->item_id
         ]);
         if (!$fight2->save()) return SERVER_ERR . "2";

         FightActionController::registerAction($user, $fight1->fight_id, $user->tag() . " begins crafting!");
         FightActionController::registerAction($user, $fight2->fight_id, self::generateQuestion($item)->toString());

         return self::generateQuestion($item);
      }

      return self::generateQuestion(new \stdClass());
   }

   public static $elements = [
      "physical" => ["Haste" => "haste", "Strength" => "strength", "Toughness" => "toughness"]
   ];
   public static $alignments = [
      "haste" =>     ["Iron"    => "iron",  "Swift Current" => "water"],
      "strength" =>  ["Earthen" => "earth", "Iron"          => "iron" ],
      "toughness" => ["Earthen" => "earth", "Swift Current" => "water"]
      // "haste" =>     ["Wind"        => "wind",     "Swift Current" => "water"   ],
      // "strength" =>  ["Earthen"     => "earth",    "Iron"          => "iron"    ],
      // "toughness" => ["Lightning"   => "electric", "Martial Art"   => "fighting"]
   ];
   // Options: Element, Raw, Luck, Defense
   public static $origins = [
      "toughness:water"=> ["Alliance"=> "alliance",   "Village"    => "village" ],
      "toughness:earth"=> ["Cliff"   => "cliff",      "Cave"       => "cave" ],
      "haste:iron"     => ["Sparrow" => "sparrow",    "Eagle"      => "eagle"],
      "haste:water"    => ["Tsunami" => "tsunami",    "Typhoon"    => "typhoon"],
      "strength:earth" => ["Forest"  => "forest",     "Cavern"     => "cavern"],
      "strength:iron"  => ["Forge"   => "forge",      "Mechanical" => "mechanical" ],
      // "wind"     => ["Sparrow" => "sparrow",    "Eagle"      => "eagle"],
      // "electric" => ["Alliance"=> "alliance",   "Village"    => "village" ],
      // "fighting" => ["Cliff"   => "cliff",      "Cave"       => "cave" ]
   ];
   public static $virtues = [
      "sparrow"    => ["freedom", "", "order", "", "", ""],
      "eagle"      => ["", "", "", "attentiveness", "individuality", ""],
      "tsunami"    => ["honor", "loyalty", "", "", "", ""],
      "typhoon"    => ["cooperation", "", "", "", "", "resilience"],
      "forest"     => ["justice", "", "loyalty", "", "", ""],
      "cavern"     => ["mystery", "", "", "", "elusiveness", ""],
      "iron"       => ["sharp", "", "heavy", "", "", ""],
      "mechanical" => ["", "", "", "", "preparedness", "strategy"],
      "alliance"   => ["", "", "", "", "diplomacy", "steadfastness"],
      "village"    => ["coalition", "", "government", "", "", ""],
      "cliff"      => ["vigilance", "", "", "support", "", ""],
      "cave"       => ["", "watchfulness", "", "", "", "strategy"]
   ];
   public static $traits = [
      "elemental" => ["wind", "electric", "forest", "cavern"],
      "physical"  => ["strength", "iron", "fighting", "iron", "typhoon", "cliff"],
      "luck"      => ["haste", "sparrow", "eagle", "tsunami", "mechanical", "alliance"],
      "defense"   => ["toughness", "water", "earth", "village", "cave"]
   ];
   public static function chooseA($word, $opts) {
      $optionsNice = [];
      $options = [];
      foreach ($opts as $option => $command) {
         $optionsNice[] = $option;
         $options[] = "craft " . $command;
      }

      return new FightWarningMessage("Choose an " . $word . ": " . implode(", ", $optionsNice) . ". `(" . implode(" | ", $options) . ")`");
   }

   public static function generateQuestion($item) {
      if (!$item->name) {
         return new FightWarningMessage("What is the name of your creation? (Prefix all commands with `craft` please)");
      }
      elseif (!$item->type) {
         return new FightWarningMessage("Is " . $item->name . " a move or an item? `(craft move | craft item)`");
      }
      else {
         $stats = $item->stats;

         if ($stats["virtue"]) {
            return new FightGoodMessage("Type `craft complete` to complete " . $item->name . "!");
         }
         elseif ($stats["element"]) {
            if ($stats["alignment"]) {
               if ($stats["origin"]) {
                  $str = "Choose a Virtue: ";
                  $virt = [];
                  foreach (self::$virtues[$stats["origin"]] as $virtue) {
                     if ($virtue) $virt[] = ucwords($virtue);
                  }
                  return new FightWarningMessage($str . implode(" or ", $virt));
               }
               else {
                  return self::chooseA("Origin", self::$origins[$stats["element"] . ":" . $stats["alignment"]]);
               }
            }
            else {
               return self::chooseA("Alignment", self::$alignments[$stats["element"]]);
            }
         }
         else {
            return self::chooseA("Element", self::$elements["physical"]);
         }
      }
   }

   public static function incStats($stats, $property) {
      if (in_array($property, self::$traits["elemental"])) {
         $stats["elemental"] += 5;
      }
      if (in_array($property, self::$traits["physical"])) {
         $stats["physical"] += 5;
      }
      if (in_array($property, self::$traits["luck"])) {
         $stats["luck"] += 5;
      }
      if (in_array($property, self::$traits["defense"])) {
         $stats["defense"] += 5;
      }

      return $stats;
   }

   public static function craft($fight, $user, $otherFight, $opponent, $action, $command) {
      if ($opponent->name !== "UCRAFTBOT") {
         return new FightDangerMessage("You're in the middle of a fight! Type `forefeit` or go to a different channel.");
      }

      $argS = strtolower(implode(array_slice($command, 1)));
      $item = FightItemModel::findOneWhere([ "item_id" => $fight->health ]);

      if ($argS) {
         if (!$item->name) {
            $item->update([ "name" => $argS ]);
         }
         elseif (!$item->type) {
            if ($argS !== "move" && $argS !== "item") {
               return new FightWarningMessage("Please enter `move` or `item`");
            }

            $item->update([ "type" => $argS ]);
         }
         elseif (!$item->stats["virtue"]) {
            $stats = $item->stats;

            if ($stats["element"]) {
               if ($stats["alignment"]) {
                  if ($stats["origin"]) {
                     $stats["virtue"] = array_search($argS, self::$virtues[$stats["origin"]]);
                     if ($stats["virtue"] === false)
                        return self::generateQuestion($item);
                     else 
                        $stats["virtue"] += 1;
                  }
                  else {
                     if (self::$virtues[$argS]) {
                        $stats["origin"] = $argS;
                     }
                     else return new FightDangerMessage("Option `" . $argS . "` not available.\n" . self::generateQuestion($item));
                  }
               }
               else {
                  if (self::$origins[$stats["element"] . ":" . $argS]) {
                     $stats["alignment"] = $argS;
                  }
                  else return new FightDangerMessage("Option `" . $argS . "` not available.\n" . self::generateQuestion($item));
               }
            }
            else {
               if (self::$alignments[$argS]) {
                  $stats["element"] = $argS;
               }
               else return new FightDangerMessage("Option `" . $argS . "` not available.\n" . self::generateQuestion($item));
            }

            $item->update([ "stats" => $stats ]);
         }
         else {
            // Mix and match based on category
            $stats = $item->stats;

            $stats["elemental"]  = 10;
            $stats["physical"]   = 15;
            $stats["defense"]    = 0;
            $stats["luck"]       = 0;

            switch($stats["virtue"]) {
               case 1:
                  $stats["elemental"] += 10;
                  $stats["physical"] += 10;
                  break;
               case 2:
                  $stats["elemental"] += 5;
                  $stats["physical"] += 10;
                  $stats["luck"] += 5;
                  break;
               case 3:
                  $stats["elemental"] += 5;
                  $stats["physical"] += 10;
                  $stats["defense"] += 5;
                  break;
               case 4:
                  $stats["physical"] += 10;
                  $stats["luck"] += 10;
                  break;
               case 5:
                  $stats["elemental"] += 5;
                  $stats["luck"] += 10;
                  $stats["defense"] += 5;
                  break;
               case 6:
                  $stats["elemental"] += 5;
                  $stats["physical"] += 5;
                  $stats["defense"] += 10;
                  break;
               default:
                  return "ZOMG You shouldn't be here!";
            }

            $stats = self::incStats($stats, $stats["origin"]);
            $stats = self::incStats($stats, $stats["alignment"]);
            $stats = self::incStats($stats, $stats["element"]);

            $item->update([ "stats" => $stats ]);

            $fight->update([ "status" => "complete" ]);
            $otherFight->update([ "status" => "complete" ]);
         
            FightActionController::registerAction($user, $fight->fight_id, $item->name . " created!");
            return new FightGoodMessage($item->name . " created!");
         }

         FightActionController::registerAction($user, $fight->fight_id, $argS);
         FightActionController::registerAction($user, $fight->fight_id, self::generateQuestion($item)->toString());
      }

      return self::generateQuestion($item);
   }
}