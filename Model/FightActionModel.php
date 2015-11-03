<?

/*
 * FightModel class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Model;

use \Fight\Data\DAO;
use \Fight\Data\DB;

class FightActionModel extends \Fight\Model\Model
{
   public static $tableName = "fight_action";

   public static $columns = [
      "fight_id" => "int",
      "action_id" => "int",
      "description" => "string",
      "actor_id" => "int",
      "created_at" => "string"
   ];

   public static $const_columns = [
      "fight_id", "action_id", "actor_id"
   ];

   public $fight_id;
   public $action_id;
   public $description;
   public $actor_id;

   // Not implemented yet you dingus
   public static $pKey = ["fight_id", "action_id"];

   public function generatePrimaryKey() {
      $dao = new DAO(get_called_class());

      // TODO HAX
      $connection = DB::getConnection();
      $q = $connection->prepare("SELECT COUNT(*) FROM " . self::$tableName . " WHERE fight_id = " . $this->fight_id);
      $q->execute();
      $r = $q->get_result();

      $actionCount = $r->fetch_row()[0];
      $this->action_id = $actionCount + 1;
   }
}
