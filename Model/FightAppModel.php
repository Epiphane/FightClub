<?

/*
 * FightAppModel class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Model;

class FightAppModel extends \Fight\Model\Model
{
   public static $tableName = "fight_app";

   public static $columns = [
      "team" => "string",
      "api_token" => "string",
      "channel" => "string"
   ];

   public static $const_columns = [
      "team"
   ];

   public $team;
   public $api_token;
   public $channel;
}
