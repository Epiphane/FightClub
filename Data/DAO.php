<?

/*
 * Data Access Object (DAO) class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Data;

use \Data\DB;
use \Data\Model;
use \Data\Collection;
use \Data\Request;
use \Data\Filter;
use \Data\InFilter;

class DAO
{
   public $connection = null;
   public $Model      = null;
   public $tableName  = null;
   public $colTypes   = [];

   public static $supported_types = [ "int" => "i", "string" => "s" ];
   public static $column_types = [ "int" => "int(11)", "string" => "varchar(64)" ];

   public function __construct($model = null) {
      if ($model) {
         $this->Model     = $model;
         $this->tableName = $model::$tableName;

         $this->setColumns($model::$columns);
      }

      $this->initDbConnection();
   }

   protected function initDbConnection() {
      $this->connection = \Data\DB::getConnection();
   }

   public function setColumns($columns) {
      foreach ($columns as $col => $type) {
         $this->colTypes[$col] = self::$supported_types[$type];
      }
   }

   public function build($model, $object) {
      $res = $model::build($object, false);

      return $res;
   }

   public function findOne($request) {
      $result = $this->find($request);

      if ($result->size() > 0) {
         return $result->first();
      }
      else {
         return null;
      }
   }

   public function formatRequest($request) {
      $query  = "";
      $values = [];
      $types  = [];

      if (count($request->Filter) > 0) {
         $qFilters = [];
         foreach ($request->Filter as $filter) {
            if ($filter instanceof InFilter) {
               $qFilters[] = $filter->property . " " . $filter->comparator . " (" . join(", ", array_fill(0, count($filter->value), "?")) . ")";
               for ($i = 0; $i < count($filter->value); $i ++) {
                  $values[] = &$filter->value[$i];
                  $types[] = $this->colTypes[$filter->property];
               }
            }
            else {
               $qFilters[] = $filter->property . " " . $filter->comparator . " ?";
               $values[] = &$filter->value;
               $types[] = $this->colTypes[$filter->property];
            }
         }

         $query .= " WHERE " . join(" AND ", $qFilters);
      }

      if (count($request->Sort) > 0) {
         $query .= " ORDER BY ";

         // Check column list
         // TODO list
         $sort = $request->Sort[0];
         if ($this->colTypes[$sort->property]) {
            $query .= $sort->property . " " . $sort->direction;
         }
      }

      if ($request->limit > 0) {
         $query .= " LIMIT " . $request->limit;
      }

      return [
         "query" => $query,
         "values" => $values,
         "types" => $types
      ];
   }

   public function bindQuery($query, $request) {
      $params = $this->formatRequest($request);
      $query  = $query . $params["query"];

      $q = $this->connection->prepare($query);

      if (count($params["values"]) > 0) {
         call_user_func_array([$q, "bind_param"], array_merge([implode($params["types"])], $params["values"]));
      }

      return $q;
   }

   public function find($request) {
      $q = $this->bindQuery("SELECT * FROM " . $this->tableName, $request);

      $result = $q->execute();
      $objects = [];
      if ($result) {
         $res = $q->get_result();
         while ($row = $res->fetch_assoc()) {
            $objects[] = $this->build($this->Model, $row);
         }
      }
         
      return new Collection($objects);
   }

   /* BE CAREFUL WITH THIS ONE DOOFUS */
   public function dropPermanently($request) {
      $q = $this->bindQuery("DELETE FROM " . $this->tableName, $request);

      $result = $q->execute();
      if ($result) {
         return $q->get_result();
      }
   }

   private function generateRandomString($length = 10) {
      $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $charactersLength = strlen($characters);
      $randomString = '';
      for ($i = 0; $i < $length; $i++) {
         $randomString .= $characters[rand(0, $charactersLength - 1)];
      }

      return $randomString;
   }

   public static function query($query) {
      $connection = \Data\DB::getConnection();
      $q = $connection->prepare($query);

      if (!$q) {
         return null;
      }

      if (func_num_args() > 1) {
         $refs = [];
         $values = array_slice(func_get_args(), 1);
         if (is_array($values[0]) && func_num_args() === 2) {
            $values = $values[0];
         }

         $types = [];
         foreach ($values as $arg) {
            if (is_numeric($arg)) {
               $types[] = "i";
            }
            else {
               $types[] = "s";
            }

            $refs[] = &$values;
         }

         call_user_func_array([$q, "bind_param"], array_merge([implode($types)], $refs));
      }

      $result = $q->execute();
      if ($result) {
         return $q->get_result();
      }
      else {
         return NULL;
      }
   }

   public function createTable($fields) {
      $tableName = $this->generateRandomString(16);
      
      $i = 0;
      $fieldNames = [];
      $associations = [];
      foreach ($fields as $column => $type) {
         if (!self::$column_types[$type]) {
            return [
               "success" => false,
               "message" => "Column type " . $type . " not supported"
            ];
         }

         $colName = "col_" . $i++;

         $associations[$column] = $colName;
         $fieldNames[] = $colName . " " . self::$column_types[$type];
      }

      $query = "CREATE TABLE {$tableName} (" . join(", ", $fieldNames) . ")";

      $q = $this->connection->prepare($query);

      $result = $q->execute();
      if ($result) {
         return [
            "table" => $tableName,
            "associations" => $associations
         ];
      }
      else {
         return NULL;
      }
   }

   public function create($model) {
      $query = "INSERT INTO " . $this->tableName;

      $vals = array();
      foreach ($this->colTypes as $column => $type) {
         if ($model->$column !== null) {
            $columns[] = "`" . $column . "`";
            $types[] = $type;

            if (is_array($model->$column)) {
               $vals[] = json_encode($model->$column);
            }
            else {
               $vals[] = $model->$column;
            }
         }
      }

      foreach ($vals as $id => $value) {
         $values[] = &$vals[$id];
      }

      $query .= " (" . join(", ", $columns) . ")";
      $query .= " VALUES (" . join(",", array_fill(0, count($values), "?")) . ")";
      
      $q = $this->connection->prepare($query);
      call_user_func_array([$q, "bind_param"], array_merge([implode($types)], $values));

      $result = $q->execute();
      if ($result) {
         return $model;
      }
      else {
         return [
            "success" => false,
            "message" => $q->error
         ];
      }
   }

   public function update($model, $attrs) {
      $query = "UPDATE " . $this->tableName . " SET ";

      $vals = [];
      foreach ($attrs as $column => $value) {
         $sets[] = $column . " = ?";
         $types[] = $this->colTypes[$column];;

         if (is_array($attrs[$column])) {
            $vals[] = json_encode($attrs[$column]);
         }
         else {
            $vals[] = $attrs[$column];
         }

         $model->$column = $value;
      }

      foreach ($vals as $id => $value) {
         $values[] = &$vals[$id];
      }

      $pKey = $model::getPrimaryKey($model);
      if (!is_array($pKey)) {
         $pKey = [$pKey];
      }

      $keys = [];
      foreach ($pKey as $key) {
         $keys[] = $key . " = ?";

         $values[] = &$model->$key;
         $types[] = $this->colTypes[$key];
      }

      $query .= join(", ", $sets) . " WHERE " . implode(" AND ", $keys);

      $q = $this->connection->prepare($query);
      call_user_func_array([$q, "bind_param"], array_merge([implode($types)], $values));

      $result = $q->execute();
      return $model;
   }
}
