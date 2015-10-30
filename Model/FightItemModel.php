<?

/*
 * FightItemModel class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Model;

class FightItemModel extends \Data\Model
{
   public static $tableName = "fight_item";

   public static $columns = [
      "item_id" => "int",
      "user_id" => "int",
      "name" => "string",
      "stats" => "string",
      "type" => "string",
      "deleted" => "int"
   ];

   public static $const_columns = [
      "item_id"
   ];

   public $item_id;
   public $user_id;
   public $name;
   public $stats;
   public $type;
   public $deleted;

   public static function build($assoc) {
      $model = forward_static_call_array([parent, "build"], func_get_args());

      if (is_string($model->stats)) {
         $model->stats = json_decode($model->stats, true);
      }

      return $model;
   }

   public function desc() {
      return [
         $this->name,
         $this->shortdesc(),
         "Type `equip (weapon|armor) " . $this->name . "` to equip this item",
         "Type `item drop " . $this->name . "` to drop this item"
      ];
   }

   public function shortdesc() {
      $output = "";
      if ($this->stats["alignment"]) {
         $output .= "`" . ucwords($this->stats["alignment"]) . "` alignment, ";
         $output .= "`" . $this->stats["elemental"] . " " . $this->stats["alignment"] . "` & ";
      }
      $output .= "`" . $this->stats["physical"] . " physical` power";

      if ($this->stats["luck"]) {
         $output .= ", `" . $this->stats["luck"] . " luck`";
      }
      if ($this->stats["defense"]) {
         $output .= ", `" . $this->stats["defense"] . " defense`";
      }

      return $output;
   }
}
