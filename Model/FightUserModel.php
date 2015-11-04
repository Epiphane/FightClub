<?

/*
 * FightUserModel class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Model;

class FightUserModel extends \Fight\Model\Model
{
   public static $tableName = "fight_user";

   public static $columns = [
      "user_id" => "int",
      "slack_name" => "string",
      "team_id" => "string",
      "AI" => "int",
      "name" => "string",
      "level" => "int",
      "experience" => "int",
      "weapon" => "int",
      "armor" => "int",
      "gold" => "int",
   ];

   public static $const_columns = [
      "user_id", "team_id"
   ];

   public $user_id;
   public $slack_name;
   public $team_id;
   public $AI;
   public $name;
   public $level;
   public $experience;
   public $weapon;
   public $armor;
   public $gold;

   public function tag() {
      if ($this->name === "UCRAFTBOT") {
         return "CraftBot";
      }
      if ($this->AI && $this->name !== "USLACKBOT") {
         return ucwords($this->name);
      }
      return "<@" . $this->name . ">";
   }
}
