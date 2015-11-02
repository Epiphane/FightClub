<?

/*
 * FightReactionModel class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Model;

class FightReactionModel extends \Fight\Model\Model
{
   public static $tableName = "fight_reaction";

   public static $columns = [
      "image_id" => "int",
      "type" => "string",
      "image_url" => "string",
      "user_id" => "int"
   ];

   public static $const_columns = [
      "image_id", "user_id"
   ];

   public $image_id;
   public $type;
   public $image_url;
   public $user_id;
}
