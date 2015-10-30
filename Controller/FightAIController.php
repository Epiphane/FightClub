<?

/*
 * FightAIController class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Controller;

use \Fight\Model\FightUserModel;
use \Fight\Model\FightItemModel;
use \Fight\Controller\FightItemController;

class FightAIController
{
   public static function computerMove($user, $fight, $computer, $otherFight) {
      $computerMoves = FightItemModel::findWhere([
         "user_id" => $computer->user_id,
         "type" => "move",
         "deleted" => 0
      ]);

      if ($computerMoves->size() === 0) {
         $move = FightItemModel::build([
            "user_id" => $computer->user_id,
            "name" => "splash",
            "stats" => [ "physical" => 0 ],
            "type" => "move"
         ]);

         $move->save();
      
         return FightItemController::useMove($move, $computer, $otherFight, $user, $fight);
      }

      return FightItemController::useMove($computerMoves->at(mt_rand(0, $computerMoves->size() - 1)), $computer, $otherFight, $user, $fight);
   }

   public static function dropItem($monster) {
      if ($monster->name === "USLACKBOT") {
         return false;
      }

      $itemDropType = mt_rand(0, $monster->level);

      if ($itemDropType < $monster->level / 10) {
         return false;
      }
      if ($itemDropType % 2 === 1) {
         return FightItemModel::findById($monster->weapon);
      }
      else {
         return FightItemModel::findById($monster->armor);
      }
   }

   public static function getRandomMonster($user) {
      $monster = FightUserModel::build([
         "team_id" => $user->team_id,
         "AI" => 1,
         "name" => FightAIController::COOL_NAME() . " the " . FightAIController::COOL_DESC(),
         "level" => mt_rand(1, $user->level + 10)
      ]);

      $monster->save();

      $monster->update([
         "weapon" => FightAIController::createRandomWeapon($monster)->item_id,
         "armor" => FightAIController::createRandomArmor($monster)->item_id
      ]);

      return $monster;
   }

   public static $VOWELS = ['A', 'E', 'I', 'O', 'U'];
   public static $LONE_CONSONANTS = ["D","J","M","QU","T"];
   public static $DOUBLE_LETTERS = ["MM","NN","TT","LL","DD","FF","SS"];
   public static $FOLLOWER_CONSONANTS = ["H","L","R"];
   public static $LEADER_CONSONANTS = ["B","C","F","G","K","P","S","T"];

   public static function randFromArray($array) {
       return $array[mt_rand(0, count($array) - 1)];
   }

   public static function COOL_NAME() {
      $result = "";

      while (strlen($result) < mt_rand(4, 8)) {
         switch(mt_rand(0, 4)) {
         case 0:
            $result .= self::randFromArray(self::$LONE_CONSONANTS);
            $result .= self::randFromArray(self::$VOWELS);
            break;
         case 1:
            if (strlen($result) > 1) {
               $result .= self::randFromArray(self::$DOUBLE_LETTERS);
               $result .= self::randFromArray(self::$VOWELS);
            }
            break;
         case 2:
            $result .= self::randFromArray(self::$LEADER_CONSONANTS);
            $result .= self::randFromArray(self::$VOWELS);
            break;
         case 3:
         case 4:
            $result .= self::randFromArray(self::$LEADER_CONSONANTS);
            $result .= self::randFromArray(self::$FOLLOWER_CONSONANTS);
            $result .= self::randFromArray(self::$VOWELS);
            break;
         }
      }

      return ucwords(strtolower($result));
   }

   public static $species = [
      "Kobold", "Whale", "Spooky Skeleton", "Dragon", "Rogue", "Elf", "Neckbeard", "Thinly-veiled threat"
   ];

   public static function COOL_DESC() {
      return self::randFromArray(self::$species);
   }

   public static $descriptors = [
      "mithril", "solid state", "steel", "powerful", "flaming", "ice", "green", "lucky", "ergonomic", "bug-infested",
      "mystical", "alphanumeric", "mysterious", "ticking", "screaming", "obviously disturbed", "ruby", "emerald",
      "sapphire", "topaz", "touchy-feely", "incredible", "cloth", "diamond", "unknown", "purple", "yellow", "blue",
      "extending", "mechanical", "wooden", "loose", "friendly", "charismatic", "rambunctious", "team-oriented", "perfectionist",
      "speeeeshul", "aloof", "ambitious", "one", "king of all", "worst", "poignant", "enticing", "legendary"
   ];

   public static $weapons = [
      "scythe", "sword", "longsword", "bow and arrows", "knife", "whip", "television", "pitchfork", "ring", "lightsaber"
   ];

   public static function createRandomWeapon($monster) {
      return self::createRandomEquip($monster, self::$weapons);
   }

   public static $armors = [
      "shield", "toolbelt", "armor", "chainmail", "helmet", "hideaway", "boots", "bulletproof vest", "hat", "earring"
   ];

   public static function createRandomArmor($monster) {
      return self::createRandomEquip($monster, self::$armors);
   }

   public static function createRandomEquip($monster, $categories) {
      $name = self::COOL_NAME() . " the " . self::randFromArray(self::$descriptors) . " " . self::randFromArray($categories);

      $stats = [
         "alignment" => self::randFromArray(FightItemController::$elements),
         "physical" => 15 + mt_rand(0, 30),
         "elemental" => 10 + mt_rand(0, 20),
         "defense" => mt_rand(0, 15)
      ];

      $item = FightItemModel::build([
         "user_id" => $monster->user_id,
         "name" => $name,
         "stats" => $stats,
         "type" => "item"
      ]);

      return $item->save();
   }
}