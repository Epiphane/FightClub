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
      "email" => "string",
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
   public $email;
   public $password;
   public $slack_name;
   public $team_id;
   public $AI;
   public $name;
   public $level;
   public $experience;
   public $weapon;
   public $armor;
   public $gold;

   public $alias;

   private static $aliases = [
      "USLACKBOT" => "<@USLACKBOT>",
      "UCRAFTBOT" => "CraftBot"
   ];

   public static function build($assoc, $new = true) {
      $model = forward_static_call_array([parent, "build"], func_get_args());

      if (!$model->AI) {
         $model->alias = FightAliasModel::findOneWhere([
            "team_id" => $model->team_id,
            "user_id" => $model->user_id
         ]);
      }

      return $model;
   }

   public function tag() {
      if ($this->alias) {
         return $this->alias->tag();
      }
      else if (self::$aliases[$this->name]) {
         return self::$aliases[$this->name];
      }
      else if ($this->AI) {
         return $this->name;
      }
   }
}
