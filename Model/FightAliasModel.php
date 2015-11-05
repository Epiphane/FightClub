<?

/*
 * FightAliasModel class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Model;

class FightAliasModel extends \Fight\Model\Model
{
   public static $tableName = "fight_alias";

   public static $columns = [
      "user_id" => "int",
      "slack_user_id" => "string",
      "team_id" => "string",
      "slack_name" => "string",
   ];

   public static $const_columns = [
      "slack_user_id", "team_id"
   ];

   public static $pKey = ["slack_user_id", "team_id"];

   public $user_id;
   public $slack_user_id;
   public $team_id;
   public $slack_name;

   public function tag() {
      if ($this->slack_user_id === "UCRAFTBOT") {
         return "CraftBot";
      }
      if ($this->AI && $this->slack_user_id !== "USLACKBOT") {
         return ucwords($this->slack_user_id);
      }
      return "<@" . $this->slack_user_id . ">";
   }
}
