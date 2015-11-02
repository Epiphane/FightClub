<?

/*
 * FightPrefsModel class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Model;

use \Data\DAO;

class FightPrefsModel extends \Data\Model
{
   public static $tableName = "fight_prefs";

   public static $columns = [
      "channel_id" => "string",
      "reactions" => "int",
      "api_token" => "string"
   ];

   public static $const_columns = [
      "channel_id"
   ];

   public static $pKey = ["channel_id"];

   public $channel_id;
   public $reactions;
   public $api_token;
}
