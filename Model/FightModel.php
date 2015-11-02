<?

/*
 * FightModel class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Model;

use \Data\DAO;

class FightModel extends \Data\Model
{
   public static $tableName = "fight";

   public static $columns = [
      "fight_id" => "int",
      "channel_id" => "string",
      "user_id" => "int",
      "status" => "string",
      "health" => "int"
   ];

   public static $const_columns = [
      "fight_id", "channel_id", "user_id"
   ];

   public static $pKey = ["fight_id", "channel_id", "user_id"];

   public $fight_id;
   public $channel_id;
   public $user_id;
   public $status;
   public $health;
}
